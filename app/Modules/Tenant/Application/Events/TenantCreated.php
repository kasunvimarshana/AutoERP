<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Events;

final readonly class TenantCreated
{
    public function __construct(
        public int|string $tenantId,
        public string $tenantCode,
    ) {
    }
}
