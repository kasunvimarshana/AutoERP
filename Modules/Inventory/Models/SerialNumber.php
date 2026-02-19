<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Contracts\Auditable as AuditableTrait;
use Modules\Inventory\Enums\SerialNumberStatus;
use Modules\Product\Models\Product;
use Modules\Tenant\Contracts\TenantScoped;

/**
 * SerialNumber Model
 *
 * Represents individual serial number tracking for serialized inventory items.
 * Tracks status, location, and reference to sales/purchase documents.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $serial_number
 * @property string $product_id
 * @property string|null $warehouse_id
 * @property string|null $location_id
 * @property SerialNumberStatus $status
 * @property string|null $batch_lot_id
 * @property string|null $reference_type
 * @property string|null $reference_id
 * @property \Carbon\Carbon|null $received_date
 * @property \Carbon\Carbon|null $sold_date
 * @property string|null $cost
 * @property string|null $warranty_months
 * @property \Carbon\Carbon|null $warranty_expiry_date
 * @property string|null $notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class SerialNumber extends Model
{
    use AuditableTrait;
    use HasFactory;
    use HasUlids;
    use SoftDeletes;
    use TenantScoped;

    protected $fillable = [
        'tenant_id',
        'serial_number',
        'product_id',
        'warehouse_id',
        'location_id',
        'status',
        'batch_lot_id',
        'reference_type',
        'reference_id',
        'received_date',
        'sold_date',
        'cost',
        'warranty_months',
        'warranty_expiry_date',
        'notes',
    ];

    protected $casts = [
        'status' => SerialNumberStatus::class,
        'received_date' => 'date',
        'sold_date' => 'date',
        'cost' => 'decimal:6',
        'warranty_months' => 'integer',
        'warranty_expiry_date' => 'date',
    ];

    /**
     * Get the product for this serial number.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the warehouse for this serial number.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the location for this serial number.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'location_id');
    }

    /**
     * Get the batch lot for this serial number.
     */
    public function batchLot(): BelongsTo
    {
        return $this->belongsTo(BatchLot::class);
    }

    /**
     * Get the stock movements for this serial number.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Get the reference model (polymorphic relationship).
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check if serial number is available.
     */
    public function isAvailable(): bool
    {
        return $this->status->isAvailable();
    }

    /**
     * Check if serial number can be sold.
     */
    public function canBeSold(): bool
    {
        return $this->status->canBeSold();
    }

    /**
     * Check if warranty is active.
     */
    public function isWarrantyActive(): bool
    {
        if ($this->warranty_expiry_date === null) {
            return false;
        }

        return now()->isBefore($this->warranty_expiry_date);
    }

    /**
     * Check if warranty is near expiry (within specified days).
     */
    public function isWarrantyNearExpiry(int $days = 30): bool
    {
        if ($this->warranty_expiry_date === null) {
            return false;
        }

        return now()->addDays($days)->isAfter($this->warranty_expiry_date)
            && $this->isWarrantyActive();
    }

    /**
     * Get days until warranty expiry.
     */
    public function getDaysUntilWarrantyExpiry(): ?int
    {
        if ($this->warranty_expiry_date === null) {
            return null;
        }

        return now()->diffInDays($this->warranty_expiry_date, false);
    }

    /**
     * Calculate warranty expiry date based on warranty months and sold date.
     */
    public function calculateWarrantyExpiryDate(): ?\Carbon\Carbon
    {
        if ($this->warranty_months === null || $this->sold_date === null) {
            return null;
        }

        return $this->sold_date->copy()->addMonths($this->warranty_months);
    }

    /**
     * Get age in days since received.
     */
    public function getAgeInDays(): ?int
    {
        if ($this->received_date === null) {
            return null;
        }

        return $this->received_date->diffInDays(now());
    }
}
