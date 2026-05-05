<?php

declare(strict_types=1);

namespace Modules\Fleet\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class VehicleModel extends Model
{
    use HasTenant, SoftDeletes;

    protected $table = 'fleet_vehicles';

    protected $fillable = [
        'tenant_id', 'org_unit_id', 'row_version', 'vehicle_type_id',
        'registration_number', 'make', 'model', 'year', 'color', 'vin_number', 'engine_number',
        'ownership_type', 'owner_supplier_id', 'owner_commission_pct',
        'is_rentable', 'is_serviceable', 'current_state', 'current_odometer',
        'fuel_type', 'fuel_capacity', 'seating_capacity', 'transmission',
        'asset_account_id', 'accum_depreciation_account_id', 'depreciation_expense_account_id',
        'rental_revenue_account_id', 'service_revenue_account_id',
        'acquisition_cost', 'acquired_at', 'disposed_at', 'metadata', 'is_active',
    ];

    protected $casts = [
        'is_rentable'           => 'boolean',
        'is_serviceable'        => 'boolean',
        'is_active'             => 'boolean',
        'current_odometer'      => 'string',
        'owner_commission_pct'  => 'string',
        'acquisition_cost'      => 'string',
        'fuel_capacity'         => 'string',
        'metadata'              => 'array',
    ];

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleTypeModel::class, 'vehicle_type_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VehicleDocumentModel::class, 'vehicle_id');
    }

    public function stateLogs(): HasMany
    {
        return $this->hasMany(VehicleStateLogModel::class, 'vehicle_id');
    }

    public function depreciationSchedule(): HasOne
    {
        return $this->hasOne(DepreciationScheduleModel::class, 'vehicle_id');
    }
}
