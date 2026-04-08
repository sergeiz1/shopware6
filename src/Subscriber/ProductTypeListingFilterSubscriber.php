<?php

declare(strict_types=1);

namespace SZ\ProductTypeExtension\Subscriber;

use Shopware\Core\Content\Product\Events\ProductListingCollectFilterEvent;
use Shopware\Core\Content\Product\SalesChannel\Listing\Filter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ProductTypeListingFilterSubscriber implements EventSubscriberInterface
{
    private const FIELD_PRODUCT_TYPE = 'productType';

    public static function getSubscribedEvents(): array
    {
        return [
            ProductListingCollectFilterEvent::class => 'addFilter',
        ];
    }

    public function addFilter(ProductListingCollectFilterEvent $event): void
    {
        $request = $event->getRequest();

        $selectedTypes = $request->get(self::FIELD_PRODUCT_TYPE, []);
        if (!is_array($selectedTypes)) {
            $selectedTypes = [$selectedTypes];
        }

        $selectedTypes = array_values(array_filter(array_map('strval', $selectedTypes)));

        $filter = new Filter(
            self::FIELD_PRODUCT_TYPE,
            \count($selectedTypes) > 0,
            [new TermsAggregation(self::FIELD_PRODUCT_TYPE, self::FIELD_PRODUCT_TYPE)],
            new EqualsAnyFilter(self::FIELD_PRODUCT_TYPE, $selectedTypes),
            $selectedTypes
        );

        $event->getFilters()->add($filter);
    }
}
