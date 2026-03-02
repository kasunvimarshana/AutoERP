<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Application\Commands;

readonly class CreateCartCommand
{
    public function __construct(
        public int $tenantId,
        public ?int $userId,
        public string $currency,
    ) {}
}
