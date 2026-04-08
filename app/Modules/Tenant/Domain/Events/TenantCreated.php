<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TenantCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $tenantId,
        public readonly string $tenantUuid,
        public readonly string $plan,
    ) {}
}
