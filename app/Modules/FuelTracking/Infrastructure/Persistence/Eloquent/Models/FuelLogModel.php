<?php

declare(strict_types=1);

namespace Modules\FuelTracking\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class FuelLogModel extends Model
{
    use HasTenant;
    use SoftDeletes;

    protected $table = 'fleet_fuel_logs';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'tenant_id',
        'org_unit_id',
        'row_version',
        'log_number',
        'vehicle_id',
        'driver_id',
        'fuel_type',
        'odometer_reading',
        'litres',
        'cost_per_litre',
        'total_cost',
        'station_name',
        'filled_at',
        'notes',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'litres'           => 'string',
        'cost_per_litre'   => 'string',
        'total_cost'       => 'string',
        'odometer_reading' => 'string',
        'metadata'         => 'array',
        'is_active'        => 'boolean',
        'filled_at'        => 'datetime',
    ];
}
