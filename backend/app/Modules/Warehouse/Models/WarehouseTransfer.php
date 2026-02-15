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

class WarehouseTransfer extends Model
{
    use HasFactory, SoftDeletes, TenantScoped, HasUuid, Auditable;

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'transfer_number',
        'reference_number',
        'source_branch_id',
        'source_location_id',
        'destination_branch_id',
        'destination_location_id',
        'status',
        'priority',
        'transfer_date',
        'expected_delivery_date',
        'actual_delivery_date',
        'shipping_cost',
        'handling_cost',
        'total_cost',
        'tracking_number',
        'carrier',
        'notes',
        'cancellation_reason',
        'metadata',
        'approved_by',
        'approved_at',
        'cancelled_by',
        'cancelled_at',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'shipping_cost' => 'decimal:2',
        'handling_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function sourceBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'source_branch_id');
    }

    public function sourceLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'source_location_id');
    }

    public function destinationBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'destination_branch_id');
    }

    public function destinationLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'destination_location_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(WarehouseTransferItem::class, 'warehouse_transfer_id');
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByBranch($query, int $branchId, string $direction = 'source')
    {
        $column = $direction === 'source' ? 'source_branch_id' : 'destination_branch_id';
        return $query->where($column, $branchId);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'pending', 'approved']);
    }

    public function scopeInTransit($query)
    {
        return $query->where('status', 'in_transit');
    }

    public function canApprove(): bool
    {
        return in_array($this->status, ['draft', 'pending']);
    }

    public function canShip(): bool
    {
        return $this->status === 'approved';
    }

    public function canReceive(): bool
    {
        return $this->status === 'in_transit';
    }

    public function canCancel(): bool
    {
        return !in_array($this->status, ['received', 'cancelled']);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'received';
    }

    public function getCompletionPercentageAttribute(): float
    {
        $totalRequested = $this->items->sum('quantity_requested');
        $totalReceived = $this->items->sum('quantity_received');
        
        if ($totalRequested <= 0) {
            return 0;
        }

        return round(($totalReceived / $totalRequested) * 100, 2);
    }
}
