<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Application\Commands;

readonly class RemoveCartItemCommand
{
    public function __construct(
        public int $tenantId,
        public string $cartToken,
        public int $itemId,
    ) {}
}
