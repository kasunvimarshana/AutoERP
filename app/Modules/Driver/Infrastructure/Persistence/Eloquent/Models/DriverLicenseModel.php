<?php

declare(strict_types=1);

namespace Modules\Driver\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverLicenseModel extends Model
{
    protected $table = 'fleet_driver_licenses';

    protected $fillable = [
        'tenant_id', 'driver_id',
        'license_number', 'license_class', 'issued_country',
        'issue_date', 'expiry_date', 'file_path', 'is_active',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'issue_date'  => 'date:Y-m-d',
        'expiry_date' => 'date:Y-m-d',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(DriverModel::class, 'driver_id');
    }
}
