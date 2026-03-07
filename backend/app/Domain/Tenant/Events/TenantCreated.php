<?php

declare(strict_types=1);

namespace App\Domain\Tenant\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Tenant;

final class TenantCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Tenant $tenant,
        public readonly int|string $createdBy,
    ) {}
}
