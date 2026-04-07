<?php

declare(strict_types=1);

namespace SZ\ProductTypeExtension\Core\Content\ProductTypeExtension;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class ProductExtension extends EntityExtension
{
    private const ASSOCIATION_PRODUCT_TYPE_EXTENSION = 'productTypeExtension';
    private const LOCAL_FIELD_PRODUCT_ID = 'id';
    private const REFERENCE_FIELD_PRODUCT_ID = 'product_id';

    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            new OneToOneAssociationField(
                self::ASSOCIATION_PRODUCT_TYPE_EXTENSION,
                self::LOCAL_FIELD_PRODUCT_ID,
                self::REFERENCE_FIELD_PRODUCT_ID,
                ProductTypeExtensionDefinition::class,
                false
            )
        );
    }

    public function getDefinitionClass(): string
    {
        return ProductDefinition::class;
    }

    public function getEntityName(): string
    {
        return ProductDefinition::ENTITY_NAME;
    }
}
