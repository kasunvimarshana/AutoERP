<?php

declare(strict_types=1);

namespace Modules\Tenancy\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Tenancy\Entities\Tenant;

/**
 * Tenant Deleted Event
 *
 * Dispatched when a tenant is deleted.
 */
class TenantDeleted
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly Tenant $tenant
    ) {}
}
