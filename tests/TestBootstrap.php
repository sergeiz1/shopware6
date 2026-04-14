<?php

declare(strict_types=1);

use Shopware\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Symfony\Component\Dotenv\Dotenv;

$pluginRoot = \dirname(__DIR__);
$projectRoot = \dirname(__DIR__, 4);

$_SERVER['PROJECT_ROOT'] = $_SERVER['PROJECT_ROOT'] ?? $projectRoot;
$_SERVER['KERNEL_CLASS'] = $_SERVER['KERNEL_CLASS'] ?? \Shopware\Core\Kernel::class;
$_SERVER['APP_ENV'] = $_SERVER['APP_ENV'] ?? 'test';
$_SERVER['APP_DEBUG'] = $_SERVER['APP_DEBUG'] ?? '1';

// Prefer DDEV database inside container to avoid localhost socket issues
$isDdev = getenv('DDEV_PROJECT') !== false;
$databaseUrl = getenv('DATABASE_URL') ?: ($_SERVER['DATABASE_URL'] ?? null);
if ($isDdev && ($databaseUrl === null || str_contains($databaseUrl, 'localhost') || str_contains($databaseUrl, '127.0.0.1'))) {
    $fallbackDsn = 'mysql://db:db@db/db?charset=utf8mb4&serverVersion=8.0';
    putenv('DATABASE_URL=' . $fallbackDsn);
    $_SERVER['DATABASE_URL'] = $fallbackDsn;
}

$classLoader = require $projectRoot . '/vendor/autoload.php';

// Ensure plugin namespace is registered even when not installed via composer in the project root.
$classLoader->addPsr4('SZ\\ProductTypeExtension\\', $pluginRoot . '/src');
$classLoader->addPsr4('SZ\\ProductTypeExtension\\Tests\\', $pluginRoot . '/tests');

if (class_exists(Dotenv::class) && is_file($projectRoot . '/.env')) {
    (new Dotenv())->usePutenv()->bootEnv($projectRoot . '/.env');
}

KernelLifecycleManager::prepare($classLoader);
