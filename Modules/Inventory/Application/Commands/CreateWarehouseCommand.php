<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Commands;

final readonly class CreateWarehouseCommand
{
    public function __construct(
        public int $tenantId,
        public string $code,
        public string $name,
        public ?string $address,
        public string $status,
    ) {}
}
