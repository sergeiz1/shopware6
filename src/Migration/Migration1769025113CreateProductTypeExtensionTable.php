<?php

declare(strict_types=1);

namespace SZ\ProductTypeExtension\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1769025113CreateProductTypeExtensionTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1769025113;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
            CREATE TABLE IF NOT EXISTS `sz_product_type_extension` (
                `id` BINARY(16) NOT NULL,
                `product_id` BINARY(16) NOT NULL,
                `product_version_id` BINARY(16) NOT NULL,
                `product_id_from_api` INT NOT NULL,
                `product_type` VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
    
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq.sz_product_type_extension.product_id_version_id` (`product_id`, `product_version_id`),
                INDEX `idx.sz_product_type_extension.product_type` (`product_type`),

                CONSTRAINT `fk.sz_product_type_extension.product`
                    FOREIGN KEY (`product_id`, `product_version_id`) 
                    REFERENCES `product` (`id`, `version_id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
            )
        ENGINE = InnoDB
        DEFAULT CHARSET = utf8mb4
        COLLATE = utf8mb4_unicode_ci;
        SQL;

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
