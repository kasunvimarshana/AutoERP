<?php

declare(strict_types=1);

namespace Modules\Supplier\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Vehicle\Models\Vehicle;

final class SupplierVehicle extends TenantOwnedModel
{
    protected $table = 'supplier_vehicles';

    protected $guarded = ['id', 'tenant_id', 'organization_unit_id', 'current_guard', 'active_guard'];

    protected function casts(): array
    {
        return ['tenant_id' => 'integer', 'organization_unit_id' => 'integer', 'supplier_id' => 'integer', 'vehicle_id' => 'integer', 'started_at' => 'datetime', 'ended_at' => 'datetime', 'is_current' => 'boolean'];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class)->withTrashed();
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }
}
