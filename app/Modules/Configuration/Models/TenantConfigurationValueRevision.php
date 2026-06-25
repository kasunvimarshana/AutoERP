<?php

declare(strict_types=1);

namespace Modules\Configuration\Models;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Models\Concerns\HasImmutableTenantOwnership;
use Modules\Core\Models\Concerns\HasTenantScope;
use Modules\Configuration\Constants\ConfigurationScope;

final class TenantConfigurationValueRevision extends ConfigurationValueRevision
{
    use HasImmutableTenantOwnership;
    use HasTenantScope;

    protected $table = 'tenant_configuration_value_revisions';

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where($this->qualifyColumn('tenant_id'), $tenantId);
    }

    public function scopeName(): string
    {
        return ConfigurationScope::TENANT;
    }

    public function organizationUnitId(): ?int
    {
        return null;
    }
}
