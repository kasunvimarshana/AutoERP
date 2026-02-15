<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Models;

use App\Core\Traits\Auditable;
use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantScoped;
use App\Models\User;
use App\Modules\Tenant\Models\Branch;
use App\Modules\Tenant\Models\Organization;
use App\Modules\Tenant\Models\Location;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarehousePicking extends Model
{
    use HasFactory, SoftDeletes, TenantScoped, HasUuid, Auditable;

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'branch_id',
        'picking_number',
        'reference_type',
        'reference_id',
        'reference_number',
        'status',
        'priority',
        'picking_type',
        'scheduled_date',
        'started_at',
        'completed_at',
        'assigned_to',
        'assigned_at',
        'picking_location_id',
        'notes',
        'cancellation_reason',
        'metadata',
        'cancelled_by',
        'cancelled_at',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'assigned_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function pickingLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'picking_location_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(WarehousePickingItem::class, 'warehouse_picking_id');
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('picking_type', $type);
    }

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function canAssign(): bool
    {
        return $this->status === 'pending';
    }

    public function canStart(): bool
    {
        return $this->status === 'assigned';
    }

    public function canComplete(): bool
    {
        return $this->status === 'in_progress';
    }

    public function canCancel(): bool
    {
        return !in_array($this->status, ['completed', 'cancelled']);
    }

    public function getCompletionPercentageAttribute(): float
    {
        $totalRequired = $this->items->sum('quantity_required');
        $totalPicked = $this->items->sum('quantity_picked');
        
        if ($totalRequired <= 0) {
            return 0;
        }

        return round(($totalPicked / $totalRequired) * 100, 2);
    }
}
