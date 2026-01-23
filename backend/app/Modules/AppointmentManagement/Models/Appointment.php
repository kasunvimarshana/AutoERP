<?php

namespace App\Modules\AppointmentManagement\Models;

use App\Modules\CustomerManagement\Models\Customer;
use App\Modules\CustomerManagement\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Appointment extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'appointment_number',
        'customer_id',
        'vehicle_id',
        'service_bay_id',
        'scheduled_date',
        'scheduled_time',
        'estimated_duration',
        'appointment_type',
        'status',
        'priority',
        'service_description',
        'customer_notes',
        'internal_notes',
        'assigned_to',
        'confirmed_at',
        'started_at',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'scheduled_date' => 'datetime',
        'scheduled_time' => 'datetime',
        'confirmed_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($appointment) {
            if (empty($appointment->uuid)) {
                $appointment->uuid = Str::uuid();
            }
            if (empty($appointment->appointment_number)) {
                $appointment->appointment_number = static::generateAppointmentNumber();
            }
        });
    }

    /**
     * Generate unique appointment number
     */
    protected static function generateAppointmentNumber(): string
    {
        do {
            $code = 'APT-' . strtoupper(Str::random(8));
        } while (static::where('appointment_number', $code)->exists());

        return $code;
    }

    /**
     * Get the tenant that owns the appointment
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /**
     * Get the customer for the appointment
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the vehicle for the appointment
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the service bay for the appointment
     */
    public function serviceBay(): BelongsTo
    {
        return $this->belongsTo(ServiceBay::class);
    }

    /**
     * Get the user assigned to the appointment
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }

    /**
     * Check if appointment is confirmed
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed' || $this->confirmed_at !== null;
    }

    /**
     * Check if appointment is in progress
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Check if appointment is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed' && $this->completed_at !== null;
    }

    /**
     * Check if appointment is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled' || $this->cancelled_at !== null;
    }

    /**
     * Get appointment full date time
     */
    public function getFullDateTimeAttribute(): string
    {
        return $this->scheduled_date->format('Y-m-d') . ' ' . $this->scheduled_time->format('H:i:s');
    }

    /**
     * Activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Scope: Active appointments (not cancelled or completed)
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['cancelled', 'completed']);
    }

    /**
     * Scope: By tenant
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: Scheduled appointments
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    /**
     * Scope: Confirmed appointments
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Scope: In progress appointments
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope: Completed appointments
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope: Cancelled appointments
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope: By priority
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope: By date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('scheduled_date', [$startDate, $endDate]);
    }

    /**
     * Scope: Upcoming appointments
     */
    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_date', '>=', now())
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time');
    }
}
