<?php

declare(strict_types=1);

namespace App\Core\Traits;

use Stancl\Tenancy\Database\Concerns\BelongsToTenant as StanclBelongsToTenant;

/**
 * Tenant Aware Trait
 * 
 * Makes models tenant-aware for multi-tenancy support
 * Automatically scopes queries to current tenant
 */
trait TenantAware
{
    use StanclBelongsToTenant;

    /**
     * Boot the tenant aware trait for a model
     *
     * @return void
     */
    public static function bootTenantAware(): void
    {
        // Additional tenant-specific boot logic can be added here
    }
}
