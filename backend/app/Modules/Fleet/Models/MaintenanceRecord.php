<?php

namespace App\Modules\Fleet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class MaintenanceRecord extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'maintenance_records';

    protected $fillable = [
        'tenant_id',
        'fleet_vehicle_id',
        'maintenance_type',
        'description',
        'service_date',
        'mileage_at_service',
        'cost',
        'performed_by',
        'next_service_date',
        'notes',
        'status',
    ];

    protected $casts = [
        'service_date' => 'date',
        'mileage_at_service' => 'integer',
        'cost' => 'decimal:2',
        'next_service_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'fleet_vehicle_id',
                'maintenance_type',
                'description',
                'service_date',
                'mileage_at_service',
                'cost',
                'performed_by',
                'next_service_date',
                'notes',
                'status',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tenant()
    {
        return $this->belongsTo(\App\Modules\Tenancy\Models\Tenant::class);
    }

    public function fleetVehicle()
    {
        return $this->belongsTo(FleetVehicle::class);
    }
}
