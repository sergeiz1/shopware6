<?php

declare(strict_types=1);

namespace SZ\ProductTypeExtension\MessageHandler;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use SZ\ProductTypeExtension\Message\ReindexProductTypeMessage;
use SZ\ProductTypeExtension\Service\ProductTypeKeywordsSyncService;
use Throwable;

#[AsMessageHandler]
final class ReindexProductTypeHandler
{
    private ?Application $application = null;
    public function __construct(
        private readonly ProductTypeKeywordsSyncService $syncService,
        private readonly KernelInterface $kernel,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(ReindexProductTypeMessage $message): void
    {
        if ($message->hardSync) {
            $this->syncService->syncAll();
        }

        $this->runCommand('dal:refresh:index');
        $this->runCommand('es:index');
        $this->runCommand('es:admin:index');
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
