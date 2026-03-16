<?php

declare(strict_types=1);

namespace SZ\ProductTypeExtension\Core\Content\ProductTypeExtension;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class ProductTypeExtensionEntity extends Entity
{
    use EntityIdTrait;

    protected string $productId;
    protected string $productVersionId;
    protected int $productIdFromApi;
    protected string $productType;

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }

    public function getProductVersionId(): string
    {
        return $this->productVersionId;
    }

    public function setProductVersionId(string $productVersionId): void
    {
        $this->productVersionId = $productVersionId;
    }

    public function getProductIdFromApi(): int
    {
        return $this->productIdFromApi;
    }

    public function setProductIdFromApi(int $productIdFromApi): void
    {
        $this->productIdFromApi = $productIdFromApi;
    }

    public function getProductType(): string
    {
        return $this->productType;
    }

    public function setProductType(string $productType): void
    {
        $this->productType = $productType;
    }
}
