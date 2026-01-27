<?php

declare(strict_types=1);

namespace Wbm\ProductTypeFilter\Subscriber;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Product\ProductEvents;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ProductTypeCustomSearchKeywordsSubscriber implements EventSubscriberInterface
{
    public const CONTEXT_STATE = 'wbm_product_type_sync';

    public function __construct(
        private readonly Connection $connection,
        /**
         * @var EntityRepository<EntityCollection<Entity>>
         */
        #[Autowire(service: 'product.repository')]
        private readonly EntityRepository $productRepository
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductEvents::PRODUCT_WRITTEN_EVENT => 'onProductWritten',
        ];
    }

    public function onProductWritten(EntityWrittenEvent $event): void
    {
        if ($event->getContext()->hasState(self::CONTEXT_STATE)) {
            return;
        }

        $productIdsBytes = $this->normalizeToBytesList($event->getIds());
        if ($productIdsBytes === []) {
            return;
        }

        // 1) Load product_type for LIVE_VERSION only (align with migration)
        $liveVersionBytes = Uuid::fromHexToBytes(Defaults::LIVE_VERSION);

        $extensionRows = $this->connection->fetchAllAssociative(
            'SELECT LOWER(HEX(product_id)) AS product_id_hex, product_type
             FROM wbm_product_type_extension
             WHERE product_id IN (:ids)
               AND product_version_id = :liveVersion',
            [
                'ids' => $productIdsBytes,
                'liveVersion' => $liveVersionBytes,
            ],
            [
                'ids' => ArrayParameterType::BINARY,
            ]
        );

        /** @var array<string, non-empty-string> $typeByProductIdHex */
        $typeByProductIdHex = [];
        foreach ($extensionRows as $row) {
            $productIdHex = strtolower((string) ($row['product_id_hex'] ?? ''));
            $type = trim((string) ($row['product_type'] ?? ''));

            if ($productIdHex === '' || $type === '') {
                continue;
            }

            $typeByProductIdHex[$productIdHex] = $type;
        }

        if ($typeByProductIdHex === []) {
            return;
        }

        // 2) Load translations including current custom_search_keywords so we can merge
        $translationRows = $this->connection->fetchAllAssociative(
            'SELECT LOWER(HEX(product_id)) AS product_id_hex, language_id, custom_search_keywords
             FROM product_translation
             WHERE product_id IN (:ids)',
            ['ids' => $productIdsBytes],
            ['ids' => ArrayParameterType::BINARY]
        );

        if ($translationRows === []) {
            return;
        }

        /** @var array<int, array<string, mixed>> $payload */
        $payload = [];

        foreach ($translationRows as $row) {
            $productIdHex = strtolower((string) ($row['product_id_hex'] ?? ''));
            $type = $typeByProductIdHex[$productIdHex] ?? null;

            if ($type === null) {
                continue;
            }

            $languageIdHex = Uuid::fromBytesToHex($row['language_id']);

            $existing = $this->decodeKeywordArray($row['custom_search_keywords'] ?? null);
            if ($this->containsCaseInsensitive($existing, $type)) {
                continue;
            }

            $existing[] = $type;

            $payload[] = [
                'id' => $productIdHex,
                'translations' => [
                    $languageIdHex => [
                        'customSearchKeywords' => array_values(array_unique($existing)),
                    ],
                ],
            ];
        }

        if ($payload === []) {
            return;
        }

        $syncContext = clone $event->getContext();
        $syncContext->addState(self::CONTEXT_STATE);

        $this->productRepository->upsert($payload, $syncContext);
    }

    /**
     * @param array<int, mixed> $ids
     * @return list<string> binary(16)
     */
    private function normalizeToBytesList(array $ids): array
    {
        $bytes = [];

        foreach ($ids as $id) {
            if (!is_string($id)) {
                continue;
            }

            $hex = strtolower(str_replace('-', '', $id));
            if (strlen($hex) !== 32) {
                continue;
            }

            $bytes[] = Uuid::fromHexToBytes($hex);
        }

        return $bytes;
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
