<?php

declare(strict_types=1);

namespace Wbm\ProductTypeFilter\Core\Content\ProductTypeExtension;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<ProductTypeExtensionEntity>
 *
 * @method void add(ProductTypeExtensionEntity $entity)
 * @method void set(string $key, ProductTypeExtensionEntity $entity)
 * @method \Traversable<string, ProductTypeExtensionEntity> getIterator()
 * @method array<string, ProductTypeExtensionEntity> getElements()
 * @method ProductTypeExtensionEntity|null get(string $key)
 * @method ProductTypeExtensionEntity|null first()
 * @method ProductTypeExtensionEntity|null last()
 */
final class ProductTypeExtensionCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ProductTypeExtensionEntity::class;
    }
}
