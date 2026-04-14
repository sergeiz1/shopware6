<?php

declare(strict_types=1);

namespace SZ\ProductTypeExtension\Tests\Util;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Uuid\Uuid;
use SZ\ProductTypeExtension\Util\UuidHelper;

class UuidHelperTest extends TestCase
{
    public function testNormalizeToBinaryListKeepsBinaryAndConvertsHex(): void
    {
        $hex = Uuid::randomHex();
        $binary = Uuid::fromHexToBytes($hex);

        $result = UuidHelper::normalizeToBinaryList([
            $hex,
            $binary,
            'not-a-uuid',
            123,
        ]);

        static::assertSame([$binary, $binary], $result);
    }

    public function testNormalizeToBinaryListIgnoresInvalidValues(): void
    {
        $result = UuidHelper::normalizeToBinaryList([
            'too-short',
            '',
            null,
            42,
        ]);

        static::assertSame([], $result);
    }
}
