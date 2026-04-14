<?php

declare(strict_types=1);

namespace SZ\ProductTypeExtension\Tests\Service;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\TestDefaults;
use SZ\ProductTypeExtension\Migration\Migration1769025113CreateProductTypeExtensionTable;
use SZ\ProductTypeExtension\Service\ProductTypeKeywordsSyncService;
use Throwable;

class ProductTypeKeywordsSyncServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    private EntityRepository $productRepository;

    private Connection $connection;

    protected function setUp(): void
    {
        $container = static::getContainer();

        /** @var EntityRepository $productRepository */
        $productRepository = $container->get('product.repository');
        $this->productRepository = $productRepository;

        /** @var Connection $connection */
        $connection = $container->get(Connection::class);
        $this->connection = $connection;

        try {
            $this->connection->fetchOne('SELECT 1');
        } catch (Throwable $e) {
            static::markTestSkipped('Database not reachable: ' . $e->getMessage());
        }

        // Make sure the plugin table exists for tests
        (new Migration1769025113CreateProductTypeExtensionTable())->update($this->connection);
    }

    public function testSyncForIdsSetsCustomSearchKeywords(): void
    {
        $context = Context::createDefaultContext();
        $productIdHex = Uuid::randomHex();
        $extensionIdHex = Uuid::randomHex();

        $this->createProduct($productIdHex, $context);
        $this->insertExtensionRow($extensionIdHex, $productIdHex, 'Books');

        $service = new ProductTypeKeywordsSyncService($this->connection);
        $service->syncForIds([$extensionIdHex]);

        $keywords = $this->fetchKeywords($productIdHex);

        static::assertSame(['Books'], $keywords);
    }

    public function testSyncClearsKeywordsWhenTypeIsEmpty(): void
    {
        $context = Context::createDefaultContext();
        $productIdHex = Uuid::randomHex();
        $extensionIdHex = Uuid::randomHex();

        $this->createProduct($productIdHex, $context);

        $this->connection->update(
            'product_translation',
            ['custom_search_keywords' => json_encode(['Existing'], JSON_THROW_ON_ERROR)],
            [
                'product_id' => Uuid::fromHexToBytes($productIdHex),
                'product_version_id' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
            ]
        );

        $this->insertExtensionRow($extensionIdHex, $productIdHex, '');

        $service = new ProductTypeKeywordsSyncService($this->connection);
        $service->syncForIds([$extensionIdHex]);

        $keywords = $this->fetchKeywords($productIdHex);

        static::assertNull($keywords);
    }

    private function createProduct(string $productIdHex, Context $context): void
    {
        $productNumber = 'p-' . substr(Uuid::randomHex(), 0, 16);

        $this->productRepository->create([[
            'id' => $productIdHex,
            'productNumber' => $productNumber,
            'stock' => 10,
            'active' => true,
            'name' => 'Test product',
            'price' => [[
                'currencyId' => Defaults::CURRENCY,
                'gross' => 10.0,
                'net' => 8.4,
                'linked' => false,
            ]],
            'taxId' => $this->getValidTaxId(),
            'manufacturer' => ['name' => 'Test GmbH'],
            'visibilities' => [[
                'salesChannelId' => TestDefaults::SALES_CHANNEL,
                'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
            ]],
        ]], $context);
    }

    private function insertExtensionRow(string $extensionIdHex, string $productIdHex, string $productType): void
    {
        $this->connection->insert('sz_product_type_extension', [
            'id' => Uuid::fromHexToBytes($extensionIdHex),
            'product_id' => Uuid::fromHexToBytes($productIdHex),
            'product_version_id' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
            'product_id_from_api' => 123,
            'product_type' => $productType,
            'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s.v'),
        ]);
    }

    /**
     * @return array<int, string>|null
     */
    private function fetchKeywords(string $productIdHex): ?array
    {
        $raw = $this->connection->fetchOne(
            'SELECT custom_search_keywords
             FROM product_translation
             WHERE product_id = :productId
               AND product_version_id = :liveVersion
             LIMIT 1',
            [
                'productId' => Uuid::fromHexToBytes($productIdHex),
                'liveVersion' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
            ]
        );

        if ($raw === false || $raw === null) {
            return null;
        }

        $rawString = (string) $raw;
        if ($rawString === '') {
            return null;
        }

        /** @var array<int, string> $decoded */
        $decoded = json_decode($rawString, true, flags: JSON_THROW_ON_ERROR);

        return $decoded;
    }
}
