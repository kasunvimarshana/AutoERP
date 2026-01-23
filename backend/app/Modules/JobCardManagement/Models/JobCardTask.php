<?php

namespace App\Modules\JobCardManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class JobCardTask extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'job_card_id',
        'task_name',
        'task_description',
        'task_type',
        'status',
        'sequence_order',
        'assigned_to',
        'estimated_minutes',
        'actual_minutes',
        'started_at',
        'completed_at',
        'completion_notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($task) {
            if (empty($task->uuid)) {
                $task->uuid = Str::uuid();
            }
        });
    }

    /**
     * Get the job card that owns the task
     */
    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class);
    }

    /**
     * Get the user assigned to the task
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }

    /**
     * Check if task is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if task is in progress
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Check if task is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if task is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Get duration in minutes
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        return $this->started_at->diffInMinutes($this->completed_at);
    }

    /**
     * Check if task is overdue based on estimated time
     */
    public function isOverdue(): bool
    {
        if (!$this->estimated_minutes || !$this->started_at || $this->isCompleted()) {
            return false;
        }

        $estimatedEnd = $this->started_at->addMinutes($this->estimated_minutes);
        return now()->gt($estimatedEnd);
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
     * Scope: Active tasks
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['cancelled', 'completed']);
    }

    /**
     * Scope: By status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: By task type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('task_type', $type);
    }

    /**
     * Scope: Assigned to user
     */
    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Scope: Order by sequence
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sequence_order');
    }
}
