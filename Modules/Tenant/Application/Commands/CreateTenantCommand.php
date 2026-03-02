<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Commands;

final readonly class CreateTenantCommand
{
    public function __construct(
        public readonly string $name,
        public readonly string $slug,
        public readonly string $planCode = 'free',
        public readonly ?string $domain = null,
        public readonly ?string $currency = null,
    ) {}
}
