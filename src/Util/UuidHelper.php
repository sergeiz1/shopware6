<?php

declare(strict_types=1);

namespace SZ\ProductTypeExtension\Util;

use Shopware\Core\Framework\Uuid\Uuid;

final class UuidHelper
{
    /**
     * @param array<int, mixed> $ids
     * @return list<string> binary(16) ids
     */
    public static function normalizeToBinaryList(array $ids): array
    {
        $bytes = [];

        foreach ($ids as $id) {
            if (!is_string($id)) {
                continue;
            }

            if (strlen($id) === 16) {
                $bytes[] = $id;
                continue;
            }

            $hex = strtolower(str_replace('-', '', $id));
            if (strlen($hex) === 32) {
                $bytes[] = Uuid::fromHexToBytes($hex);
            }
        }

        return $bytes;
    }
}
