<?php

declare(strict_types=1);

namespace Modules\Tenancy\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Tenancy\Entities\Tenant;

/**
 * Tenant Updated Event
 *
 * Dispatched when a tenant is updated.
 */
class TenantUpdated
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly Tenant $tenant
    ) {}
}
