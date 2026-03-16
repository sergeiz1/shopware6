<?php

declare(strict_types=1);

namespace SZ\ProductTypeFilter\Subscriber;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Plugin\Event\PluginPostActivateEvent;
use Shopware\Core\Framework\Plugin\Event\PluginPostInstallEvent;
use Shopware\Core\Framework\Plugin\Event\PluginPostUpdateEvent;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Throwable;

final class PluginLifecycleSubscriber implements EventSubscriberInterface
{
    private ?Application $application = null;

    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly LoggerInterface $logger,
        private readonly ProductTypeKeywordsSyncSubscriber $syncSubscriber
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PluginPostInstallEvent::class  => 'reindex',
            PluginPostUpdateEvent::class   => 'reindex',
            PluginPostActivateEvent::class => 'reindex',
        ];
    }

    public function reindex(object $event = null): void
    {
        $eventClass = $event !== null ? $event::class : 'unknown';
        $this->logger->info(sprintf('[WBM] Reindex triggered (plugin lifecycle, event=%s).', $eventClass));

        $isHard = $event instanceof PluginPostInstallEvent || $event instanceof PluginPostActivateEvent;

        if ($isHard) {
            $this->syncSubscriber->syncAll();
        }

        $this->runCommand('cache:clear', ['--no-interaction' => true]);
        $this->runCommand('dal:refresh:index', ['--no-interaction' => true]);
        $this->runCommand('es:index', ['--no-interaction' => true]);
        $this->runCommand('es:admin:index', ['--no-interaction' => true]);

        $this->logger->info('Reindex finished.');
    }

    /**
     * @param array<string, bool|int|string|null> $options
     */
    private function runCommand(string $commandName, array $options = []): void
    {
        $app = $this->getApplication();

        if (!$app->has($commandName)) {
            $this->logger->warning(sprintf('Command not found: %s (skipping)', $commandName));
            return;
        }

        $input = new ArrayInput(array_merge(['command' => $commandName], $options));
        $output = new BufferedOutput();

        $this->logger->info(sprintf('Running command: %s', $commandName));

        try {
            $exitCode = $app->run($input, $output);
        } catch (Throwable $e) {
            $this->logger->error(sprintf('Command threw exception: %s', $commandName), [
                'exception' => $e,
            ]);
            return;
        }

        $buffer = trim($output->fetch());
        if ($buffer !== '') {
            $snippet = mb_substr($buffer, 0, 4000);
            $this->logger->info(sprintf('Output %s: %s', $commandName, $snippet));
        }

        if ($exitCode !== 0) {
            $this->logger->error(sprintf('Command failed (%s) exitCode=%d', $commandName, $exitCode));
            return;
        }

        $this->logger->info(sprintf('Command OK: %s', $commandName));
    }

    private function getApplication(): Application
    {
        if ($this->application instanceof Application) {
            return $this->application;
        }

        $app = new Application($this->kernel);
        $app->setAutoExit(false);

        return $this->application = $app;
    }
}
