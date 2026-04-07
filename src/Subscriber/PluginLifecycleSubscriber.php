<?php

declare(strict_types=1);

namespace SZ\ProductTypeExtension\Subscriber;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Plugin\Event\PluginPostActivateEvent;
use Shopware\Core\Framework\Plugin\Event\PluginPostUpdateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use SZ\ProductTypeExtension\Message\ReindexProductTypeMessage;

final class PluginLifecycleSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PluginPostActivateEvent::class => 'reindex',
            PluginPostUpdateEvent::class   => 'reindex',
        ];
    }

    public function reindex(object $event): void
    {
        $isHard = $event instanceof PluginPostActivateEvent;

        $this->messageBus->dispatch(
            new ReindexProductTypeMessage(hardSync: $isHard)
        );

        $this->logger->info('Reindex message dispatched.');
    }
}
