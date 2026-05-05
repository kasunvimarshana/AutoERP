<?php

declare(strict_types=1);

namespace Modules\Fleet\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class VehicleTypeModel extends Model
{
    use HasTenant, SoftDeletes;

    protected $table = 'fleet_vehicle_types';

    protected $fillable = [
        'tenant_id', 'org_unit_id', 'row_version',
        'name', 'description', 'base_daily_rate', 'base_hourly_rate',
        'seating_capacity', 'is_active',
    ];

    protected $casts = [
        'base_daily_rate'  => 'string',
        'base_hourly_rate' => 'string',
        'is_active'        => 'boolean',
    ];

    public function vehicles(): HasMany
    {
        return $this->hasMany(VehicleModel::class, 'vehicle_type_id');
    }
}
