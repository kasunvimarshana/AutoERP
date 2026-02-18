<?php

namespace App\Modules\Manufacturing\Models;

use App\Models\Tenant;
use App\Models\User;
use App\Modules\Manufacturing\Enums\WorkOrderStatus;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkOrder extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\Manufacturing\WorkOrderFactory::new();
    }

    protected $fillable = [
        'tenant_id',
        'work_order_number',
        'production_order_id',
        'workstation',
        'description',
        'scheduled_start',
        'scheduled_end',
        'actual_start',
        'actual_end',
        'status',
        'notes',
        'assigned_to',
        'started_by',
        'completed_by',
    ];

    protected $casts = [
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
        'actual_start' => 'datetime',
        'actual_end' => 'datetime',
        'status' => WorkOrderStatus::class,
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function scopeByStatus($query, WorkOrderStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', WorkOrderStatus::PENDING);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', WorkOrderStatus::IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', WorkOrderStatus::COMPLETED);
    }

    /**
     * Calculate actual duration in minutes
     */
    public function getActualDurationMinutes(): ?int
    {
        if (! $this->actual_start || ! $this->actual_end) {
            return null;
        }

        return $this->actual_start->diffInMinutes($this->actual_end);
    }
}
