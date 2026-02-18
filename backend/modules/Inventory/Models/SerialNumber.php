<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\BelongsToTenant;
use Modules\Inventory\Enums\SerialNumberStatus;

/**
 * Serial Number Model
 *
 * Represents individual serial-tracked items in the inventory.
 * Used for products that require unit-level tracking for warranty,
 * service history, and asset management.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $product_id
 * @property string|null $variant_id
 * @property string|null $batch_id
 * @property string $serial_number
 * @property string|null $warehouse_id
 * @property string|null $location_id
 * @property SerialNumberStatus $status
 * @property string|null $customer_id
 * @property string|null $sale_order_id
 * @property \Illuminate\Support\Carbon|null $sale_date
 * @property \Illuminate\Support\Carbon|null $warranty_start_date
 * @property \Illuminate\Support\Carbon|null $warranty_end_date
 * @property float|null $purchase_cost
 * @property string|null $notes
 * @property array|null $custom_attributes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class SerialNumber extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'serial_numbers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'product_id',
        'variant_id',
        'batch_id',
        'serial_number',
        'warehouse_id',
        'location_id',
        'status',
        'customer_id',
        'sale_order_id',
        'sale_date',
        'warranty_start_date',
        'warranty_end_date',
        'purchase_cost',
        'notes',
        'custom_attributes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => SerialNumberStatus::class,
        'sale_date' => 'datetime',
        'warranty_start_date' => 'datetime',
        'warranty_end_date' => 'datetime',
        'purchase_cost' => 'float',
        'custom_attributes' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the product that owns this serial number
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the product variant that owns this serial number
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * Get the batch that this serial number belongs to
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    /**
     * Get the warehouse where this serial number is located
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * Get the location where this serial number is stored
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    /**
     * Check if serial number is in stock
     */
    public function isInStock(): bool
    {
        return $this->status === SerialNumberStatus::IN_STOCK;
    }

    /**
     * Check if serial number is sold
     */
    public function isSold(): bool
    {
        return $this->status === SerialNumberStatus::SOLD;
    }

    /**
     * Check if warranty is active
     */
    public function hasActiveWarranty(): bool
    {
        if (!$this->warranty_end_date) {
            return false;
        }

        return now()->isBefore($this->warranty_end_date) && 
               (!$this->warranty_start_date || now()->isAfter($this->warranty_start_date));
    }

    /**
     * Get remaining warranty days
     */
    public function getRemainingWarrantyDays(): ?int
    {
        if (!$this->warranty_end_date || !$this->hasActiveWarranty()) {
            return null;
        }

        return max(0, now()->diffInDays($this->warranty_end_date, false));
    }

    /**
     * Scope to get available serial numbers (in stock)
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', SerialNumberStatus::IN_STOCK);
    }

    /**
     * Scope to get sold serial numbers
     */
    public function scopeSold($query)
    {
        return $query->where('status', SerialNumberStatus::SOLD);
    }

    /**
     * Scope to get serial numbers with active warranty
     */
    public function scopeWithActiveWarranty($query)
    {
        return $query->whereNotNull('warranty_end_date')
            ->where('warranty_end_date', '>', now())
            ->where(function ($q) {
                $q->whereNull('warranty_start_date')
                    ->orWhere('warranty_start_date', '<=', now());
            });
    }
}
