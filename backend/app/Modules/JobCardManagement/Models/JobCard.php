<?php

namespace App\Modules\JobCardManagement\Models;

use App\Modules\CustomerManagement\Models\Customer;
use App\Modules\CustomerManagement\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class JobCard extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'tenant_id',
        'job_card_number',
        'appointment_id',
        'customer_id',
        'vehicle_id',
        'service_bay_id',
        'status',
        'priority',
        'opened_at',
        'started_at',
        'completed_at',
        'invoiced_at',
        'opened_by',
        'assigned_to',
        'current_mileage',
        'estimated_hours',
        'actual_hours',
        'customer_complaint',
        'diagnosis',
        'work_performed',
        'internal_notes',
        'estimated_cost',
        'actual_cost',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'invoiced_at' => 'datetime',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($jobCard) {
            if (empty($jobCard->uuid)) {
                $jobCard->uuid = Str::uuid();
            }
            if (empty($jobCard->job_card_number)) {
                $jobCard->job_card_number = static::generateJobCardNumber();
            }
        });
    }

    /**
     * Generate unique job card number
     */
    protected static function generateJobCardNumber(): string
    {
        do {
            $code = 'JC-' . strtoupper(Str::random(8));
        } while (static::where('job_card_number', $code)->exists());

        return $code;
    }

    /**
     * Get the tenant that owns the job card
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /**
     * Get the customer for this job card
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the vehicle for this job card
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the user who opened the job card
     */
    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'opened_by');
    }

    /**
     * Get the user assigned to the job card
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }

    /**
     * Get the tasks for this job card
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(JobCardTask::class);
    }

    /**
     * Get the digital inspections for this job card
     */
    public function digitalInspections(): HasMany
    {
        return $this->hasMany(DigitalInspection::class);
    }

    /**
     * Check if job card is open
     */
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /**
     * Check if job card is in progress
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Check if job card is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if job card is on hold
     */
    public function isOnHold(): bool
    {
        return $this->status === 'on_hold';
    }

    /**
     * Check if job card is invoiced
     */
    public function isInvoiced(): bool
    {
        return $this->status === 'invoiced';
    }

    /**
     * Check if job card is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if job card is draft
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if job card is high priority
     */
    public function isHighPriority(): bool
    {
        return in_array($this->priority, ['high', 'urgent']);
    }

    /**
     * Get completion percentage based on tasks
     */
    public function getCompletionPercentageAttribute(): int
    {
        $totalTasks = $this->tasks()->count();
        if ($totalTasks === 0) {
            return 0;
        }

        $completedTasks = $this->tasks()->where('status', 'completed')->count();
        return (int) round(($completedTasks / $totalTasks) * 100);
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
     * Scope: Active job cards
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['cancelled', 'completed', 'invoiced']);
    }

    /**
     * Scope: By tenant
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: By status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: By priority
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope: High priority
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }

    /**
     * Scope: Assigned to user
     */
    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }
}
