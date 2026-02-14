<?php

namespace App\Modules\Appointments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'vehicle_id',
        'service_bay_id',
        'appointment_number',
        'scheduled_date',
        'scheduled_time',
        'duration_minutes',
        'service_type',
        'status',
        'notes',
        'confirmed_at',
        'completed_at',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_minutes' => 'integer',
    ];

    public function tenant()
    {
        return $this->belongsTo(\App\Modules\Tenancy\Models\Tenant::class);
    }

    public function customer()
    {
        return $this->belongsTo(\App\Modules\CRM\Models\Customer::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(\App\Modules\CRM\Models\Vehicle::class);
    }

    public function serviceBay()
    {
        return $this->belongsTo(ServiceBay::class);
    }

    protected static function newFactory()
    {
        return \App\Modules\Appointments\Database\Factories\AppointmentFactory::new();
    }
}
