<?php

declare(strict_types=1);

namespace Wbm\ProductTypeFilter\Core\Content\ProductTypeExtension;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\SearchRanking;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class ProductTypeExtensionDefinition extends EntityDefinition
{
    private const ENTITY_NAME = 'wbm_product_type_extension';
    private const FIELD_ID = 'id';
    private const FIELD_PRODUCT_ID = 'product_id';
    private const FIELD_PRODUCT_ID_FROM_API = 'product_id_from_api';
    private const FIELD_PRODUCT_TYPE = 'product_type';
    private const ASSOCIATION_PRODUCT = 'product';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return ProductTypeExtensionEntity::class;
    }

    public function getCollectionClass(): string
    {
        return ProductTypeExtensionCollection::class;
    }

    public function getDefaults(): array
    {
        return [];
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField(self::FIELD_ID, self::FIELD_ID))->addFlags(new Required(), new PrimaryKey(), new ApiAware()),
            (new IdField(self::FIELD_PRODUCT_ID, 'productId'))->addFlags(new Required(), new ApiAware()),
            (new ReferenceVersionField(ProductDefinition::class))->addFlags(new Required()),
            (new IntField(self::FIELD_PRODUCT_ID_FROM_API, 'productIdFromApi'))->addFlags(new Required(), new ApiAware()),
            (new StringField(self::FIELD_PRODUCT_TYPE, 'productType'))->addFlags(new ApiAware(), new SearchRanking(SearchRanking::HIGH_SEARCH_RANKING)),
            new OneToOneAssociationField(self::ASSOCIATION_PRODUCT, self::FIELD_PRODUCT_ID, self::FIELD_ID, ProductDefinition::class, false),
            new CreatedAtField(),
            new UpdatedAtField(),
        ]);
    }
}
