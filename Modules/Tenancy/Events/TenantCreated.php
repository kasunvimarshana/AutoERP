<?php

declare(strict_types=1);

namespace Modules\Tenancy\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Tenancy\Entities\Tenant;

/**
 * Tenant Created Event
 *
 * Dispatched when a new tenant is created.
 */
class TenantCreated
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly Tenant $tenant
    ) {}
}
