<?php

declare(strict_types=1);

namespace Modules\Procurement\Application\Commands;

final readonly class CreateSupplierCommand
{
    public function __construct(
        public int $tenantId,
        public string $name,
        public ?string $contactName,
        public ?string $email,
        public ?string $phone,
        public ?string $address,
        public ?string $notes,
    ) {}
}
