<?php

namespace App\Modules\Appointments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceBay extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'bay_number',
        'name',
        'capacity',
        'status',
        'current_appointment_id',
    ];

    protected $casts = [
        'capacity' => 'integer',
    ];

    public function tenant()
    {
        return $this->belongsTo(\App\Modules\Tenancy\Models\Tenant::class);
    }

    public function currentAppointment()
    {
        return $this->belongsTo(Appointment::class, 'current_appointment_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    protected static function newFactory()
    {
        return \App\Modules\Appointments\Database\Factories\ServiceBayFactory::new();
    }
}
