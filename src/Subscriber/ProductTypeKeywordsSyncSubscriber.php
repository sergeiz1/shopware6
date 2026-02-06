<?php

declare(strict_types=1);

namespace Wbm\ProductTypeFilter\Subscriber;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductTypeKeywordsSyncSubscriber implements EventSubscriberInterface
{
    private const TABLE_EXTENSION = 'wbm_product_type_extension';

    private string $liveVersionBytes;

    public function __construct(
        private readonly Connection $connection
    ) {
        $this->liveVersionBytes = Uuid::fromHexToBytes(Defaults::LIVE_VERSION);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'wbm_product_type_extension.written' => 'onProductTypeWritten',
        ];
    }

    public function onProductTypeWritten(EntityWrittenEvent $event): void
    {
        $extensionIdsBinary = $this->normalizeIdsToBinary($event->getIds());
        if ($extensionIdsBinary === []) {
            return;
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT product_id, product_type
             FROM `' . self::TABLE_EXTENSION . '`
             WHERE id IN (:ids)
               AND product_version_id = :liveVersion',
            [
                'ids' => $extensionIdsBinary,
                'liveVersion' => $this->liveVersionBytes,
            ],
            [
                'ids' => ArrayParameterType::BINARY,
            ]
        );

        if ($rows === []) {
            return;
        }

        $this->applyKeywordsForRows($rows);
    }

    // Initial fill: call this once after plugin install/activate
    public function syncAll(): void
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT product_id, product_type
             FROM `' . self::TABLE_EXTENSION . '`
             WHERE product_version_id = :liveVersion',
            ['liveVersion' => $this->liveVersionBytes]
        );

        if ($rows === []) {
            return;
        }

        $this->applyKeywordsForRows($rows);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function applyKeywordsForRows(array $rows): void
    {
        /** @var array<string, string> $setMap */
        $setMap = [];
        /** @var list<string> $clearIds */
        $clearIds = [];
        /** @var array<string, string> $jsonCache */
        $jsonCache = [];

        foreach ($rows as $row) {
            $productId = $row['product_id'] ?? null;
            if (!\is_string($productId) || $productId === '') {
                continue;
            }

            $productType = trim((string) ($row['product_type'] ?? ''));
            if ($productType === '') {
                $clearIds[] = $productId;
                continue;
            }

            $keywordsJson = $jsonCache[$productType]
                ??= json_encode([$productType], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

            $setMap[$productId] = $keywordsJson;
        }

        // Bulk clear
        if ($clearIds !== []) {
            $this->connection->executeStatement(
                'UPDATE product_translation
                 SET custom_search_keywords = NULL
                 WHERE product_id IN (:productIds)
                   AND product_version_id = :liveVersion',
                [
                    'productIds' => $clearIds,
                    'liveVersion' => $this->liveVersionBytes,
                ],
                [
                    'productIds' => ArrayParameterType::BINARY,
                ]
            );
        }

        if ($setMap === []) {
            return;
        }

        // Bulk set using CASE
        $caseSql = [];
        $params = [
            'liveVersion' => $this->liveVersionBytes,
            'productIds' => array_keys($setMap),
        ];
        $types = [
            'productIds' => ArrayParameterType::BINARY,
        ];

        $i = 0;
        foreach ($setMap as $productId => $keywordsJson) {
            $paramId = 'k' . $i;
            $caseSql[] = "WHEN :p{$i} THEN :{$paramId}";
            $params["p{$i}"] = $productId;
            $params[$paramId] = $keywordsJson;
            $i++;
        }

        $sql = sprintf(
            'UPDATE product_translation
             SET custom_search_keywords = CASE product_id %s ELSE custom_search_keywords END
             WHERE product_id IN (:productIds)
              AND product_version_id = :liveVersion',
            "\n" . implode("\n", $caseSql) . "\n"
        );

        $this->connection->executeStatement($sql, $params, $types);
    }

    /**
     * @param array<int, mixed> $ids
     * @return list<string> binary(16)
     */
    private function normalizeIdsToBinary(array $ids): array
    {
        $binaryIds = [];

        foreach ($ids as $id) {
            if (!\is_string($id)) {
                continue;
            }

            if (strlen($id) === 16) {
                $binaryIds[] = $id;
                continue;
            }

            $hex = strtolower(str_replace('-', '', $id));
            if (strlen($hex) !== 32) {
                continue;
            }

            $binaryIds[] = Uuid::fromHexToBytes($hex);
        }

        return $binaryIds;
    }
}
