<?php

declare(strict_types=1);

namespace Wbm\ProductTypeFilter\Elasticsearch\Product;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use OpenSearchDSL\BuilderInterface;
use OpenSearchDSL\Query\Compound\BoolQuery;
use OpenSearchDSL\Query\TermLevel\TermQuery;
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

    public function __construct(
        private readonly AbstractElasticsearchDefinition $inner,
        private readonly Connection $connection
    ) {
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

        $productIdsBytes = $this->normalizeToBytesList($ids);
        if ($productIdsBytes === []) {
            return $data;
        }

        $liveVersionBytes = Uuid::fromHexToBytes(Defaults::LIVE_VERSION);

        $rows = $this->connection->fetchAllAssociative(
            'SELECT LOWER(HEX(product_id)) AS product_id_hex, product_type
             FROM `' . self::TABLE_EXTENSION . '`
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

        /** @var array<string, string> $typeByProductIdHex */
        $typeByProductIdHex = [];
        foreach ($rows as $row) {
            $productIdHex = strtolower((string) ($row['product_id_hex'] ?? ''));
            $type = trim((string) ($row['product_type'] ?? ''));

            if ($productIdHex === '' || $type === '') {
                continue;
            }

            $typeByProductIdHex[$productIdHex] = $type;
        }

        foreach ($data as $id => &$doc) {
            $productIdHex = $this->normalizeToHex((string) $id);
            if ($productIdHex === null) {
                continue;
            }

            $type = $typeByProductIdHex[$productIdHex] ?? '';
            if ($type === '') {
                continue;
            }

            $doc[self::ES_FIELD_PRODUCT_TYPE] = $type;

            if (!isset($doc['customSearchKeywords']) || !is_array($doc['customSearchKeywords'])) {
                $doc['customSearchKeywords'] = [];
            }

            foreach ($doc['customSearchKeywords'] as $languageId => $keywords) {
                if (is_array($keywords)) {
                    $list = array_values(array_filter(array_map('trim', $keywords), static fn ($v) => $v !== ''));

                    // Optional: case-insensitive dupe protection
                    $lower = array_map(static fn (string $v) => mb_strtolower($v), $list);
                    if (!in_array(mb_strtolower($type), $lower, true)) {
                        $list[] = $type;
                    }

                    $doc['customSearchKeywords'][$languageId] = $list;
                    continue;
                }

                // Fallback for legacy/string formats
                $doc['customSearchKeywords'][$languageId] = trim((string) $keywords . ' ' . $type);
            }
        }
        unset($doc);

        return $data;
    }

    public function buildTermQuery(Context $context, Criteria $criteria): BuilderInterface
    {
        $query = $this->inner->buildTermQuery($context, $criteria);

        $term = trim((string) $criteria->getTerm());
        if ($term === '') {
            return $query;
        }

        // Note: TermQuery on keyword field is exact match (case-sensitive depending on mapping/normalizer).
        if ($query instanceof BoolQuery) {
            $query->add(new TermQuery(self::ES_FIELD_PRODUCT_TYPE, $term), BoolQuery::SHOULD);
        }

        return $query;
    }

    /**
     * @param array<int, mixed> $ids
     * @return list<string> binary(16) ids
     */
    private function normalizeToBytesList(array $ids): array
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

    private function normalizeToHex(string $id): ?string
    {
        if (strlen($id) === 16) {
            return strtolower(Uuid::fromBytesToHex($id));
        }

        $hex = strtolower(str_replace('-', '', $id));
        if (strlen($hex) === 32) {
            return $hex;
        }

        return null;
    }
}
