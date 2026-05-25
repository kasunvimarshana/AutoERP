<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Events;

final readonly class TenantStatusChanged
{
    public function __construct(
        public int|string $tenantId,
        public string $status,
        public bool $isActive,
    ) {
    }
}
