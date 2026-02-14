<?php

namespace App\Modules\Inventory\Models;

use App\Base\BaseModel;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Product\Models\ProductVariant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="BatchLot",
 *     type="object",
 *     title="BatchLot",
 *     description="Batch/Lot tracking model for FIFO/FEFO",
 *     @OA\Property(property="id", type="string", format="uuid"),
 *     @OA\Property(property="tenant_id", type="string", format="uuid"),
 *     @OA\Property(property="branch_id", type="string", format="uuid"),
 *     @OA\Property(property="product_variant_id", type="string", format="uuid"),
 *     @OA\Property(property="batch_number", type="string", example="BATCH-001"),
 *     @OA\Property(property="lot_number", type="string", example="LOT-001"),
 *     @OA\Property(property="quantity_received", type="integer", example=100),
 *     @OA\Property(property="quantity_remaining", type="integer", example=50),
 *     @OA\Property(property="unit_cost", type="number", format="float", example=10.50),
 *     @OA\Property(property="manufacture_date", type="string", format="date"),
 *     @OA\Property(property="expiry_date", type="string", format="date"),
 *     @OA\Property(property="status", type="string", enum={"active", "expired", "depleted"}),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time")
 * )
 */
class BatchLot extends BaseModel
{
    protected $table = 'batch_lots';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'product_variant_id',
        'batch_number',
        'lot_number',
        'quantity_received',
        'quantity_remaining',
        'unit_cost',
        'manufacture_date',
        'expiry_date',
        'status',
    ];

    protected $casts = [
        'quantity_received' => 'integer',
        'quantity_remaining' => 'integer',
        'unit_cost' => 'decimal:2',
        'manufacture_date' => 'date',
        'expiry_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Organization::class, 'branch_id');
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || ($this->expiry_date && $this->expiry_date->isPast());
    }

    public function isDepleted(): bool
    {
        return $this->status === 'depleted' || $this->quantity_remaining <= 0;
    }

    public function daysUntilExpiry(): ?int
    {
        if (!$this->expiry_date) {
            return null;
        }

        return now()->diffInDays($this->expiry_date, false);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('quantity_remaining', '>', 0);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeDepleted($query)
    {
        return $query->where('status', 'depleted');
    }

    public function scopeByTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByBranch($query, string $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByVariant($query, string $variantId)
    {
        return $query->where('product_variant_id', $variantId);
    }

    public function scopeByBatch($query, string $batchNumber)
    {
        return $query->where('batch_number', $batchNumber);
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('status', 'active')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays($days))
            ->whereDate('expiry_date', '>=', now());
    }

    public function scopeOrderByFIFO($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    public function scopeOrderByFEFO($query)
    {
        return $query->orderBy('expiry_date', 'asc')
            ->orderBy('created_at', 'asc');
    }
}
