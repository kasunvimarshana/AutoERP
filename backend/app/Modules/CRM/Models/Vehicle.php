<?php

namespace App\Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * Vehicle Model
 * 
 * @property int $id
 * @property int $tenant_id
 * @property int $customer_id
 * @property string $make
 * @property string $model
 * @property int $year
 * @property string $vin
 * @property string $license_plate
 * @property string $color
 * @property int $mileage
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 * 
 * @package App\Modules\CRM\Models
 */
class Vehicle extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'make',
        'model',
        'year',
        'vin',
        'license_plate',
        'color',
        'mileage',
    ];

    protected $casts = [
        'year' => 'integer',
        'mileage' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['make', 'model', 'year', 'vin', 'license_plate', 'mileage'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tenant()
    {
        return $this->belongsTo(\App\Modules\Tenancy\Models\Tenant::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
