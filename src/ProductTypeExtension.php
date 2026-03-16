<?php

declare(strict_types=1);

namespace SZ\ProductTypeExtension;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Uuid\Uuid;

class ProductTypeExtension extends Plugin
{
    private const TABLE_NAME = 'sz_product_type_extension';
    private const DEMO_TYPES = [
        'Bücher',
        'CD-ROMs',
        'DVD-ROMs',
        'Zeitschriften',
    ];

    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);
    }

    public function postInstall(InstallContext $installContext): void
    {
        parent::postInstall($installContext);

        $this->insertTestProductTypes($this->getConnection());
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        $this->getConnection()->executeStatement(
            sprintf('DROP TABLE IF EXISTS `%s`', self::TABLE_NAME)
        );
    }

    private function insertTestProductTypes(Connection $connection): void
    {
        $exists = (int) $connection->fetchOne(
            'SELECT COUNT(*)
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
               AND table_name = :tableName',
            ['tableName' => self::TABLE_NAME]
        );

        if ($exists === 0) {
            return;
        }

        $count = (int) $connection->fetchOne(
            sprintf('SELECT COUNT(*) FROM `%s`', self::TABLE_NAME)
        );

        if ($count > 0) {
            return;
        }

        $liveVersionBytes = Uuid::fromHexToBytes(Defaults::LIVE_VERSION);
        $productIds = $connection->fetchFirstColumn(
            'SELECT id
             FROM product
             WHERE active = 1
               AND version_id = :liveVersion
             ORDER BY created_at DESC
             LIMIT 5',
            ['liveVersion' => $liveVersionBytes]
        );

        if ($productIds === []) {
            return;
        }

        $i = 0;
        foreach ($productIds as $productIdBytes) {
            $type = self::DEMO_TYPES[$i % \count(self::DEMO_TYPES)];
            $productIdFromApi = $i + 1;

            $connection->executeStatement(
                sprintf(
                    'INSERT INTO `%s`
                        (id, product_id, product_version_id, product_id_from_api, product_type, created_at, updated_at)
                     VALUES
                        (:id, :productId, :productVersionId, :productIdFromApi, :type, NOW(3), NOW(3))
                     ON DUPLICATE KEY UPDATE
                        product_id_from_api = VALUES(product_id_from_api),
                        product_type = VALUES(product_type),
                        updated_at = NOW(3)',
                    self::TABLE_NAME
                ),
                [
                    'id' => Uuid::randomBytes(),
                    'productId' => $productIdBytes,
                    'productVersionId' => $liveVersionBytes,
                    'productIdFromApi' => $productIdFromApi,
                    'type' => $type,
                ]
            );

            $i++;
        }
    }

    private function getConnection(): Connection
    {
        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);

        return $connection;
    }
}
