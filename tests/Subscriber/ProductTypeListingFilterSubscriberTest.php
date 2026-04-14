<?php

declare(strict_types=1);

namespace SZ\ProductTypeExtension\Tests\Subscriber;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\Events\ProductListingCollectFilterEvent;
use Shopware\Core\Content\Product\SalesChannel\Listing\Filter;
use Shopware\Core\Content\Product\SalesChannel\Listing\FilterCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use SZ\ProductTypeExtension\Subscriber\ProductTypeListingFilterSubscriber;

class ProductTypeListingFilterSubscriberTest extends TestCase
{
    private ProductTypeListingFilterSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->subscriber = new ProductTypeListingFilterSubscriber();
    }

    public function testSelectedTypesAreAddedAsFilteredListingFilter(): void
    {
        $request = new Request(['productType' => ['Books', 'DVD']]);
        $filters = new FilterCollection();
        $context = $this->createMock(SalesChannelContext::class);
        $context->method('getContext')->willReturn(Context::createDefaultContext());

        $event = new ProductListingCollectFilterEvent($request, $filters, $context);

        $this->subscriber->addFilter($event);

        static::assertTrue($filters->has('productType'));

        $filter = $filters->get('productType');
        static::assertInstanceOf(Filter::class, $filter);
        static::assertTrue($filter->isFiltered());
        static::assertSame(['Books', 'DVD'], $filter->getValues());

        $aggregations = $filter->getAggregations();
        static::assertCount(1, $aggregations);
        static::assertInstanceOf(TermsAggregation::class, $aggregations[0]);
        static::assertSame('productType', $aggregations[0]->getName());
    }

    public function testEmptySelectionCreatesUnfilteredListingFilter(): void
    {
        $request = new Request(); // no productType parameter
        $filters = new FilterCollection();
        $context = $this->createMock(SalesChannelContext::class);
        $context->method('getContext')->willReturn(Context::createDefaultContext());

        $event = new ProductListingCollectFilterEvent($request, $filters, $context);

        $this->subscriber->addFilter($event);

        static::assertTrue($filters->has('productType'));

        $filter = $filters->get('productType');
        static::assertInstanceOf(Filter::class, $filter);
        static::assertFalse($filter->isFiltered());
        static::assertSame([], $filter->getValues());
    }
}
