<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Models\Concerns\HasImmutableTenantOwnership;
use Modules\Core\Models\Concerns\HasTenantScope;

/**
 * Base model for records owned by exactly one tenant.
 *
 * Reads and writes fail closed outside a trusted tenant execution boundary.
 * Cross-tenant system work must use the explicit control-plane context and narrow
 * each tenant-specific operation with TenantExecutionContext.
 */
abstract class TenantOwnedModel extends CoreModel
{
    use HasImmutableTenantOwnership;
    use HasTenantScope;

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where($this->qualifyColumn('tenant_id'), $tenantId);
    }
}
