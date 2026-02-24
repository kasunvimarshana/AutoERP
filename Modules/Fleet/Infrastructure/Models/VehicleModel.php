<?php

namespace Modules\Fleet\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class VehicleModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'fleet_vehicles';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'plate_number',
        'make',
        'model',
        'year',
        'color',
        'fuel_type',
        'vin',
        'assigned_to',
        'status',
        'notes',
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
