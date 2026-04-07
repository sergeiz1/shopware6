<?php

declare(strict_types=1);

namespace SZ\ProductTypeExtension\Message;

final class ReindexProductTypeMessage
{
    public function __construct(
        public readonly bool $hardSync = false
    ) {
    }
}
