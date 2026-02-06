<?php

declare(strict_types=1);

namespace Wbm\ProductTypeFilter\Elasticsearch\Product;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use OpenSearchDSL\BuilderInterface;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Elasticsearch\Framework\AbstractElasticsearchDefinition;

class ElasticsearchProductDefinitionDecorator extends AbstractElasticsearchDefinition
{
    private const TABLE_EXTENSION = 'wbm_product_type_extension';
    private const ES_FIELD_PRODUCT_TYPE = 'wbmProductType';

    private string $liveVersionBytes;

    public function __construct(
        private readonly AbstractElasticsearchDefinition $inner,
        private readonly Connection $connection
    ) {
        $this->liveVersionBytes = Uuid::fromHexToBytes(Defaults::LIVE_VERSION);
    }

    public function getEntityDefinition(): EntityDefinition
    {
        return $this->inner->getEntityDefinition();
    }

    public function getMapping(Context $context): array
    {
        $mapping = $this->inner->getMapping($context);
        $mapping['properties'][self::ES_FIELD_PRODUCT_TYPE] = self::KEYWORD_FIELD;

        return $mapping;
    }

    public function fetch(array $ids, Context $context): array
    {
        $data = $this->inner->fetch($ids, $context);

        if ($ids === []) {
            return $data;
        }

        $productIdsBinary = $this->normalizeToBinaryList($ids);
        if ($productIdsBinary === []) {
            return $data;
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT p.id AS doc_id,
                    ext.product_type
            FROM product p
            LEFT JOIN `' . self::TABLE_EXTENSION . '` ext
              ON ext.product_id = COALESCE(p.parent_id, p.id)
             AND ext.product_version_id = :liveVersion
            WHERE p.id IN (:ids)
              AND p.version_id = :liveVersion',
            [
                'ids' => $productIdsBinary,
                'liveVersion' => $this->liveVersionBytes,
            ],
            [
                'ids' => ArrayParameterType::BINARY,
            ]
        );

        /** @var array<string, string> $typeByProductIdBinary */
        $typeByProductIdBinary = [];
        foreach ($rows as $row) {
            $productIdBinary = $row['doc_id'] ?? null;
            if (!is_string($productIdBinary) || $productIdBinary === '' || strlen($productIdBinary) !== 16) {
                continue;
            }

            $type = trim((string) ($row['product_type'] ?? ''));
            if ($type === '') {
                continue;
            }

            $typeByProductIdBinary[$productIdBinary] = $type;
        }

        if ($typeByProductIdBinary === []) {
            return $data;
        }

        foreach ($data as $id => &$doc) {
            if (!is_string($id) || strlen($id) !== 16) {
                $hex = strtolower(str_replace('-', '', (string) $id));
                if (strlen($hex) !== 32) {
                    continue;
                }
                $id = Uuid::fromHexToBytes($hex);
            }

            $type = $typeByProductIdBinary[$id] ?? null;
            if ($type === null) {
                continue;
            }

            $doc[self::ES_FIELD_PRODUCT_TYPE] = $type;
        }
        unset($doc);

        return $data;
    }

    public function buildTermQuery(Context $context, Criteria $criteria): BuilderInterface
    {
        return $this->inner->buildTermQuery($context, $criteria);
    }

    /**
     * @param array<int, mixed> $ids
     * @return list<string> binary(16) ids
     */
    private function normalizeToBinaryList(array $ids): array
    {
        $bytes = [];

        foreach ($ids as $id) {
            if (!is_string($id)) {
                continue;
            }

            if (strlen($id) === 16) {
                $bytes[] = $id;
                continue;
            }

            $hex = strtolower(str_replace('-', '', $id));
            if (strlen($hex) === 32) {
                $bytes[] = Uuid::fromHexToBytes($hex);
            }
        }

        return $bytes;
    }
}
