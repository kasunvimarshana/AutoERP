<?php

namespace App\Modules\JobCards\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class JobCard extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'job_cards';

    protected $fillable = [
        'tenant_id',
        'appointment_id',
        'customer_id',
        'vehicle_id',
        'job_card_number',
        'opened_date',
        'closed_date',
        'status',
        'technician_id',
        'estimated_cost',
        'actual_cost',
        'notes',
    ];

    protected $casts = [
        'opened_date' => 'datetime',
        'closed_date' => 'datetime',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'job_card_number',
                'appointment_id',
                'customer_id',
                'vehicle_id',
                'opened_date',
                'closed_date',
                'status',
                'technician_id',
                'estimated_cost',
                'actual_cost',
                'notes',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tenant()
    {
        return $this->belongsTo(\App\Modules\Tenancy\Models\Tenant::class);
    }

    public function appointment()
    {
        return $this->belongsTo(\App\Modules\Appointments\Models\Appointment::class);
    }

    public function customer()
    {
        return $this->belongsTo(\App\Modules\CRM\Models\Customer::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(\App\Modules\Fleet\Models\Vehicle::class);
    }

    public function technician()
    {
        return $this->belongsTo(\App\Modules\Identity\Models\User::class, 'technician_id');
    }

    public function tasks()
    {
        return $this->hasMany(JobCardTask::class);
    }
}
