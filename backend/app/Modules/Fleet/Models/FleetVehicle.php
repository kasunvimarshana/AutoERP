<?php

namespace App\Modules\Fleet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class FleetVehicle extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'fleet_vehicles';

    protected $fillable = [
        'tenant_id',
        'vehicle_number',
        'make',
        'model',
        'year',
        'vin',
        'license_plate',
        'color',
        'mileage',
        'fuel_type',
        'status',
        'last_service_date',
        'next_service_due',
        'notes',
    ];

    protected $casts = [
        'year' => 'integer',
        'mileage' => 'integer',
        'last_service_date' => 'date',
        'next_service_due' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'vehicle_number',
                'make',
                'model',
                'year',
                'vin',
                'license_plate',
                'color',
                'mileage',
                'fuel_type',
                'status',
                'last_service_date',
                'next_service_due',
                'notes',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tenant()
    {
        return $this->belongsTo(\App\Modules\Tenancy\Models\Tenant::class);
    }

    public function maintenanceRecords()
    {
        return $this->hasMany(MaintenanceRecord::class);
    }
}
