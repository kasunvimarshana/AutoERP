<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\BaseModel;
use Modules\Core\Traits\BelongsToTenant;

/**
 * Batch Model
 *
 * Represents a batch of products for inventory tracking.
 * Used for products that require batch-level tracking for quality control,
 * traceability, and expiry management.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $product_id
 * @property string|null $variant_id
 * @property string $batch_number
 * @property string|null $lot_number
 * @property string|null $supplier_id
 * @property \Illuminate\Support\Carbon|null $manufacture_date
 * @property \Illuminate\Support\Carbon|null $expiry_date
 * @property float $received_quantity
 * @property float $available_quantity
 * @property float|null $unit_cost
 * @property string|null $notes
 * @property array|null $custom_attributes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Batch extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'batches';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'product_id',
        'variant_id',
        'batch_number',
        'lot_number',
        'supplier_id',
        'manufacture_date',
        'expiry_date',
        'received_quantity',
        'available_quantity',
        'unit_cost',
        'notes',
        'custom_attributes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'manufacture_date' => 'datetime',
        'expiry_date' => 'datetime',
        'received_quantity' => 'float',
        'available_quantity' => 'float',
        'unit_cost' => 'float',
        'custom_attributes' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the product that owns this batch
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the product variant that owns this batch
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * Get the serial numbers associated with this batch
     */
    public function serialNumbers(): HasMany
    {
        return $this->hasMany(SerialNumber::class, 'batch_id');
    }

    /**
     * Check if batch is expired
     */
    public function isExpired(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }

        return now()->isAfter($this->expiry_date);
    }

    /**
     * Check if batch is near expiry (within specified days)
     */
    public function isNearExpiry(int $days = 30): bool
    {
        if (!$this->expiry_date) {
            return false;
        }

        return now()->diffInDays($this->expiry_date, false) <= $days && !$this->isExpired();
    }

    /**
     * Get remaining shelf life in days
     */
    public function getRemainingShelfLife(): ?int
    {
        if (!$this->expiry_date) {
            return null;
        }

        return max(0, now()->diffInDays($this->expiry_date, false));
    }

    /**
     * Scope to get non-expired batches
     */
    public function scopeNonExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expiry_date')
                ->orWhere('expiry_date', '>', now());
        });
    }

    /**
     * Scope to get batches near expiry
     */
    public function scopeNearExpiry($query, int $days = 30)
    {
        return $query->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays($days)]);
    }

    /**
     * Scope to get available batches (with stock)
     */
    public function scopeAvailable($query)
    {
        return $query->where('available_quantity', '>', 0);
    }
}
