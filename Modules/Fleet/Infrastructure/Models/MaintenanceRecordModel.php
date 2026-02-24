<?php

namespace Modules\Fleet\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class MaintenanceRecordModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'fleet_maintenance_records';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'vehicle_id',
        'maintenance_type',
        'performed_at',
        'cost',
        'odometer_km',
        'performed_by',
        'notes',
    ];

    protected $casts = [
        'performed_at' => 'date',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }
}
