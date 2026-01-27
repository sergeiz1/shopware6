<?php

declare(strict_types=1);

namespace Wbm\ProductTypeFilter\Command;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'wbm:product-type:sync',
    description: 'Sync product_type from wbm_product_type_extension into product.customSearchKeywords (all languages)'
)]
class ProductTypeSyncCommand extends Command
{
    private const TABLE_NAME = 'wbm_product_type_extension';
    private const CHUNK_SIZE = 250;

    public function __construct(
        private readonly Connection $connection,
        /**
         * @var EntityRepository<ProductCollection>
         */
        #[Autowire(service: 'product.repository')]
        private readonly EntityRepository $productRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Only show what would be updated, without writing changes')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Limit number of products to process (debug)', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $context = Context::createDefaultContext();
        $dryRun = (bool) $input->getOption('dry-run');

        $limitInt = $this->parsePositiveInt($input->getOption('limit'));
        $limitSql = $limitInt !== null ? ' LIMIT ' . $limitInt : '';

        $extensionRows = $this->connection->fetchAllAssociative(
            sprintf(
                'SELECT LOWER(HEX(product_id)) AS id, product_type
                 FROM `%s`
                 WHERE product_type <> ""%s',
                self::TABLE_NAME,
                $limitSql
            )
        );

        if ($extensionRows === []) {
            $output->writeln(sprintf('<info>No rows found in %s.</info>', self::TABLE_NAME));
            return Command::SUCCESS;
        }

        /** @var array<string, string> $productTypeByProductIdHex */
        $productTypeByProductIdHex = [];
        /** @var list<string> $productIdsBytes */
        $productIdsBytes = [];

        foreach ($extensionRows as $row) {
            $productIdHex = strtolower((string) ($row['id'] ?? ''));
            $productType = trim((string) ($row['product_type'] ?? ''));

            if (strlen($productIdHex) !== 32 || $productType === '') {
                continue;
            }

            $productTypeByProductIdHex[$productIdHex] = $productType;
            $productIdsBytes[] = Uuid::fromHexToBytes($productIdHex);
        }

        if ($productIdsBytes === []) {
            $output->writeln('<info>No valid product IDs to sync.</info>');
            return Command::SUCCESS;
        }

        $translationRows = $this->connection->fetchAllAssociative(
            'SELECT LOWER(HEX(product_id)) AS id, language_id, custom_search_keywords
             FROM product_translation
             WHERE product_id IN (:ids)',
            ['ids' => $productIdsBytes],
            ['ids' => ArrayParameterType::BINARY]
        );

        if ($translationRows === []) {
            $output->writeln('<comment>No product_translation rows found.</comment>');
            return Command::SUCCESS;
        }

        /** @var array<string, array<int, string>> $existingKeywordsByProductAndLanguage */
        $existingKeywordsByProductAndLanguage = [];

        foreach ($translationRows as $row) {
            $productIdHex = strtolower((string) ($row['id'] ?? ''));
            $languageIdHex = Uuid::fromBytesToHex($row['language_id']);

            $existingKeywordsByProductAndLanguage[$productIdHex . ':' . $languageIdHex] =
                $this->decodeKeywordArray($row['custom_search_keywords'] ?? null);
        }

        /** @var array<int, array<string, mixed>> $upsertPayload */
        $upsertPayload = [];
        $updatedTranslations = 0;
        $skippedTranslations = 0;

        foreach ($translationRows as $row) {
            $productIdHex = strtolower((string) ($row['id'] ?? ''));

            $productType = $productTypeByProductIdHex[$productIdHex] ?? null;
            if ($productType === null) {
                $skippedTranslations++;
                continue;
            }

            $languageIdHex = Uuid::fromBytesToHex($row['language_id']);
            $mapKey = $productIdHex . ':' . $languageIdHex;

            $existing = $existingKeywordsByProductAndLanguage[$mapKey] ?? [];

            if ($this->containsCaseInsensitive($existing, $productType)) {
                $skippedTranslations++;
                continue;
            }

            $merged = $existing;
            $merged[] = $productType;

            $upsertPayload[] = [
                'id' => $productIdHex,
                'translations' => [
                    $languageIdHex => [
                        'customSearchKeywords' => array_values(array_unique($merged)),
                    ],
                ],
            ];

            $updatedTranslations++;

            if (\count($upsertPayload) >= self::CHUNK_SIZE) {
                $this->flushUpserts($upsertPayload, $context, $dryRun);
                $upsertPayload = [];
            }
        }

        $this->flushUpserts($upsertPayload, $context, $dryRun);

        $output->writeln(sprintf(
            $dryRun
                ? '<info>DRY RUN: Would update %d translations. Skipped %d.</info>'
                : '<info>Done. Updated %d translations. Skipped %d.</info>',
            $updatedTranslations,
            $skippedTranslations
        ));

        if (!$dryRun) {
            $output->writeln('<comment>Now run: bin/console dal:refresh:index</comment>');
        }

        return Command::SUCCESS;
    }

    /**
     * @param array<int, array<string, mixed>> $payload
     */
    private function flushUpserts(array $payload, Context $context, bool $dryRun): void
    {
        if ($payload === [] || $dryRun) {
            return;
        }

        $this->productRepository->upsert($payload, $context);
    }

    private function parsePositiveInt(mixed $value): ?int
    {
        if (!is_string($value) || $value === '' || !ctype_digit($value)) {
            return null;
        }

        $int = (int) $value;

        return $int > 0 ? $int : null;
    }

    /**
     * @return array<int, string>
     */
    private function decodeKeywordArray(mixed $raw): array
    {
        if (!is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $keywords = [];
        foreach ($decoded as $item) {
            if (!is_string($item)) {
                continue;
            }

            $v = trim($item);
            if ($v !== '') {
                $keywords[] = $v;
            }
        }

        return array_values($keywords);
    }

    /**
     * @param array<int, string> $haystack
     */
    private function containsCaseInsensitive(array $haystack, string $needle): bool
    {
        $needleLower = mb_strtolower($needle);

        foreach ($haystack as $value) {
            if (mb_strtolower($value) === $needleLower) {
                return true;
            }
        }

        return false;
    }
}
