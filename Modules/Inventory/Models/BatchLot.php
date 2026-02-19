<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Audit\Contracts\Auditable as AuditableTrait;
use Modules\Product\Models\Product;
use Modules\Tenant\Contracts\TenantScoped;

/**
 * BatchLot Model
 *
 * Represents batch/lot tracking records for inventory.
 * Supports manufacturing dates, expiry dates, and batch-specific quantity tracking.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $batch_number
 * @property string $product_id
 * @property string $warehouse_id
 * @property string|null $location_id
 * @property string $quantity
 * @property string $reserved_quantity
 * @property string $available_quantity
 * @property \Carbon\Carbon|null $manufacture_date
 * @property \Carbon\Carbon|null $expiry_date
 * @property \Carbon\Carbon|null $received_date
 * @property string|null $supplier_batch_number
 * @property string|null $cost
 * @property bool $is_active
 * @property string|null $notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class BatchLot extends Model
{
    use AuditableTrait;
    use HasFactory;
    use HasUlids;
    use SoftDeletes;
    use TenantScoped;

    protected $fillable = [
        'tenant_id',
        'batch_number',
        'product_id',
        'warehouse_id',
        'location_id',
        'quantity',
        'reserved_quantity',
        'available_quantity',
        'manufacture_date',
        'expiry_date',
        'received_date',
        'supplier_batch_number',
        'cost',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:6',
        'reserved_quantity' => 'decimal:6',
        'available_quantity' => 'decimal:6',
        'manufacture_date' => 'date',
        'expiry_date' => 'date',
        'received_date' => 'date',
        'cost' => 'decimal:6',
        'is_active' => 'boolean',
    ];

    /**
     * Get the product for this batch lot.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the warehouse for this batch lot.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the location for this batch lot.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'location_id');
    }

    /**
     * Get the stock movements for this batch lot.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Calculate available quantity (quantity - reserved_quantity).
     */
    public function calculateAvailableQuantity(): string
    {
        return bcsub((string) $this->quantity, (string) $this->reserved_quantity, 6);
    }

    /**
     * Check if batch is expired.
     */
    public function isExpired(): bool
    {
        if ($this->expiry_date === null) {
            return false;
        }

        return now()->isAfter($this->expiry_date);
    }

    /**
     * Check if batch is near expiry (within specified days).
     */
    public function isNearExpiry(int $days = 30): bool
    {
        if ($this->expiry_date === null) {
            return false;
        }

        return now()->addDays($days)->isAfter($this->expiry_date) && ! $this->isExpired();
    }

    /**
     * Get days until expiry.
     */
    public function getDaysUntilExpiry(): ?int
    {
        if ($this->expiry_date === null) {
            return null;
        }

        return now()->diffInDays($this->expiry_date, false);
    }

    /**
     * Check if batch has available quantity.
     */
    public function hasAvailableQuantity(): bool
    {
        return bccomp((string) $this->available_quantity, '0', 6) > 0;
    }

    /**
     * Check if batch can be used (active and not expired).
     */
    public function canBeUsed(): bool
    {
        return $this->is_active && ! $this->isExpired() && $this->hasAvailableQuantity();
    }

    /**
     * Get shelf life percentage (remaining life / total life * 100).
     */
    public function getShelfLifePercentage(): ?string
    {
        if ($this->manufacture_date === null || $this->expiry_date === null) {
            return null;
        }

        $totalLife = $this->manufacture_date->diffInDays($this->expiry_date);
        if ($totalLife <= 0) {
            return '0.00';
        }

        $remainingLife = now()->diffInDays($this->expiry_date, false);
        if ($remainingLife < 0) {
            return '0.00';
        }

        return bcdiv(
            bcmul((string) $remainingLife, '100', 2),
            (string) $totalLife,
            2
        );
    }
}
