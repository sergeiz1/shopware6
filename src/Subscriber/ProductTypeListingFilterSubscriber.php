<?php

declare(strict_types=1);

namespace Wbm\ProductTypeFilter\Subscriber;

use Shopware\Core\Content\Product\Events\ProductListingCollectFilterEvent;
use Shopware\Core\Content\Product\SalesChannel\Listing\Filter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductTypeListingFilterSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ProductListingCollectFilterEvent::class => 'addFilter',
        ];
    }

    public function addFilter(ProductListingCollectFilterEvent $event): void
    {
        $filters = $event->getFilters();
        $request = $event->getRequest();

        $selectedTypes = $event->getRequest()->get('wbmProductType', []);
        if (!is_array($selectedTypes)) {
            $selectedTypes = [$selectedTypes];
        }
        $selectedTypes = array_values(array_filter(array_map('strval', $selectedTypes)));

        $isFiltered = \count($selectedTypes) > 0;

        $filter = new Filter(
            'wbmProductType',
            $isFiltered,
            [new TermsAggregation('wbmProductType', 'wbmProductType')],
            new EqualsAnyFilter('wbmProductType', $selectedTypes),
            $selectedTypes
        );
        $filters->add($filter);
    }
}
