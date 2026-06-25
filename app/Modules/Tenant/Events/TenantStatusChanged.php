<?php

declare(strict_types=1);

namespace Modules\Tenant\Events;

final readonly class TenantStatusChanged
{
    public function __construct(
        public string $eventId,
        public int $tenantId,
        public string $previousStatus,
        public string $newStatus,
        public ?string $reason,
    ) {}
}
