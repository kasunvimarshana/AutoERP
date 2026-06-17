<?php

declare(strict_types=1);

namespace Modules\Vehicle\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\CoreModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;
use Modules\Vehicle\Enums\VehicleFuelType;
use Modules\Vehicle\Enums\VehicleStatus;
use Modules\Vehicle\Enums\VehicleTransmissionType;

final class Vehicle extends CoreModel
{
    use SoftDeletes;

    protected $table = 'vehicles';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'vehicle_make_id' => 'integer',
            'vehicle_model_id' => 'integer',
            'vehicle_type_id' => 'integer',
            'vehicle_category_id' => 'integer',
            'manufacture_year' => 'integer',
            'registration_date' => 'date',
            'fuel_type' => VehicleFuelType::class,
            'transmission_type' => VehicleTransmissionType::class,
            'odometer_reading' => 'decimal:6',
            'status' => VehicleStatus::class,
            'metadata' => 'array',
            'approved_by' => 'integer',
            'approved_at' => 'datetime',
        ]);
    }

    public function tenant(): BelongsTo { return $this->belongsTo(TenantModel::class, 'tenant_id'); }
    public function organizationUnit(): BelongsTo { return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id'); }
    public function make(): BelongsTo { return $this->belongsTo(VehicleMake::class, 'vehicle_make_id'); }
    public function model(): BelongsTo { return $this->belongsTo(VehicleModel::class, 'vehicle_model_id'); }
    public function type(): BelongsTo { return $this->belongsTo(VehicleType::class, 'vehicle_type_id'); }
    public function category(): BelongsTo { return $this->belongsTo(VehicleCategory::class, 'vehicle_category_id'); }
    public function documents(): HasMany { return $this->hasMany(VehicleDocument::class, 'vehicle_id'); }
    public function statusHistories(): HasMany { return $this->hasMany(VehicleStatusHistory::class, 'vehicle_id'); }
    public function ownerships(): HasMany { return $this->hasMany(VehicleOwnership::class, 'vehicle_id'); }
    public function currentOwnership(): HasOne { return $this->hasOne(VehicleOwnership::class, 'vehicle_id')->where('is_current', true); }
    public function attributes(): HasMany { return $this->hasMany(VehicleAttribute::class, 'vehicle_id'); }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', VehicleStatus::Active);
    }

    public function scopeForTenant(Builder $query, int $tenantId, ?int $organizationUnitId = null): Builder
    {
        $query->where('tenant_id', $tenantId);
        return $organizationUnitId === null ? $query : $query->where(function (Builder $scope) use ($organizationUnitId): void {
            $scope->whereNull('organization_unit_id')->orWhere('organization_unit_id', $organizationUnitId);
        });
    }
}
