<?php

declare(strict_types=1);

namespace Modules\Vehicle\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\TenantOwnedModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Vehicle\Enums\VehicleOwnerType;
use Modules\Vehicle\Enums\VehicleOwnershipType;

final class VehicleOwnership extends TenantOwnedModel
{
    protected $table = 'vehicle_ownerships';

    protected $guarded = ['id', 'tenant_id', 'organization_unit_id', 'owner_key', 'owner_code_snapshot', 'owner_name_snapshot', 'current_guard', 'active_guard'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'vehicle_id' => 'integer',
            'owner_type' => VehicleOwnerType::class,
            'owner_id' => 'integer',
            'ownership_type' => VehicleOwnershipType::class,
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'is_current' => 'boolean',
        ]);
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id')->withTrashed();
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }

    public function scopeForOwnerType(Builder $query, VehicleOwnerType|string $ownerType): Builder
    {
        return $query->where('owner_type', $ownerType instanceof VehicleOwnerType ? $ownerType->value : $ownerType);
    }
}
