<?php

declare(strict_types=1);

namespace Modules\Driver\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class DriverModel extends Model
{
    use HasTenant, SoftDeletes;

    protected $table = 'fleet_drivers';

    protected $fillable = [
        'tenant_id', 'org_unit_id', 'row_version', 'employee_id',
        'driver_code', 'full_name', 'phone', 'email', 'address',
        'compensation_type', 'per_trip_rate', 'commission_pct',
        'status', 'metadata', 'is_active',
    ];

    protected $casts = [
        'is_active'       => 'boolean',
        'per_trip_rate'   => 'string',
        'commission_pct'  => 'string',
        'metadata'        => 'array',
    ];

    public function licenses(): HasMany
    {
        return $this->hasMany(DriverLicenseModel::class, 'driver_id');
    }
}
