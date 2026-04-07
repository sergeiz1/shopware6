<?php

declare(strict_types=1);

namespace SZ\ProductTypeExtension\Subscriber;

use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use SZ\ProductTypeExtension\Service\ProductTypeKeywordsSyncService;

class ProductTypeKeywordsSyncSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ProductTypeKeywordsSyncService $syncService
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'sz_product_type_extension.written' => 'onProductTypeWritten',
        ];
    }

    public function onProductTypeWritten(EntityWrittenEvent $event): void
    {
        $this->syncService->syncForIds($event->getIds());
    }
}
