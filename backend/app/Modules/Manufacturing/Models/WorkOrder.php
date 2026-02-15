<?php

declare(strict_types=1);

namespace App\Modules\Manufacturing\Models;

use App\Core\Traits\Auditable;
use App\Core\Traits\HasUuid;
use App\Core\Traits\TenantScoped;
use App\Models\User;
use App\Modules\Product\Models\Product;
use App\Modules\Tenant\Models\Branch;
use App\Modules\Tenant\Models\Organization;
use App\Modules\Tenant\Models\Location;
use App\Modules\MasterData\Models\UnitOfMeasure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * WorkOrder Model
 * 
 * Represents a production/manufacturing work order for producing products
 */
class WorkOrder extends Model
{
    use HasFactory, SoftDeletes, TenantScoped, HasUuid, Auditable;

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'branch_id',
        'location_id',
        'product_id',
        'bom_id',
        'work_order_number',
        'reference_number',
        'status',
        'priority',
        'planned_quantity',
        'produced_quantity',
        'scrap_quantity',
        'unit_id',
        'planned_start_date',
        'planned_end_date',
        'actual_start_date',
        'actual_end_date',
        'estimated_cost',
        'actual_cost',
        'material_cost',
        'labor_cost',
        'overhead_cost',
        'notes',
        'production_instructions',
        'metadata',
        'assigned_to',
        'approved_by',
        'approved_at',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'planned_quantity' => 'decimal:4',
        'produced_quantity' => 'decimal:4',
        'scrap_quantity' => 'decimal:4',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'material_cost' => 'decimal:2',
        'labor_cost' => 'decimal:2',
        'overhead_cost' => 'decimal:2',
        'planned_start_date' => 'date',
        'planned_end_date' => 'date',
        'actual_start_date' => 'datetime',
        'actual_end_date' => 'datetime',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the product being manufactured
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the BOM being used
     */
    public function bom(): BelongsTo
    {
        return $this->belongsTo(BillOfMaterial::class, 'bom_id');
    }

    /**
     * Get the branch
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the organization
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the location
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the unit of measure
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'unit_id');
    }

    /**
     * Get the user assigned to this work order
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who approved this work order
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who cancelled this work order
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Get all work order items
     */
    public function items(): HasMany
    {
        return $this->hasMany(WorkOrderItem::class, 'work_order_id');
    }

    /**
     * Scope to filter by status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by priority
     */
    public function scopePriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope to get work orders within date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('planned_start_date', [$startDate, $endDate]);
    }

    /**
     * Scope to get in-progress work orders
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope to get completed work orders
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Calculate completion percentage
     */
    public function getCompletionPercentageAttribute(): float
    {
        if ($this->planned_quantity <= 0) {
            return 0;
        }

        return round(($this->produced_quantity / $this->planned_quantity) * 100, 2);
    }

    /**
     * Get remaining quantity to produce
     */
    public function getRemainingQuantityAttribute(): float
    {
        return max(0, $this->planned_quantity - $this->produced_quantity);
    }

    /**
     * Check if work order is overdue
     */
    public function isOverdue(): bool
    {
        if (!$this->planned_end_date) {
            return false;
        }

        return $this->planned_end_date < now() && !in_array($this->status, ['completed', 'cancelled']);
    }

    /**
     * Check if work order can be started
     */
    public function canStart(): bool
    {
        return in_array($this->status, ['planned', 'released']);
    }

    /**
     * Check if work order can be completed
     */
    public function canComplete(): bool
    {
        return $this->status === 'in_progress' && $this->produced_quantity > 0;
    }

    /**
     * Check if work order can be cancelled
     */
    public function canCancel(): bool
    {
        return !in_array($this->status, ['completed', 'cancelled']);
    }
}
