<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Tenant;
use App\Models\OrderItem;

/**
 * Domain entity representing a Product in the Inventory bounded context.
 *
 * Note: In this architecture the Domain Entity extends Eloquent for pragmatic
 * reasons while keeping the domain logic encapsulated within the entity itself.
 */
class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'tenant_id',
        'sku',
        'name',
        'description',
        'category',
        'price',
        'cost',
        'stock_quantity',
        'reserved_quantity',
        'reorder_point',
        'reorder_quantity',
        'unit',
        'weight',
        'dimensions',
        'attributes',
        'is_active',
        'is_trackable',
    ];

    protected $casts = [
        'price'             => 'decimal:4',
        'cost'              => 'decimal:4',
        'stock_quantity'    => 'integer',
        'reserved_quantity' => 'integer',
        'reorder_point'     => 'integer',
        'reorder_quantity'  => 'integer',
        'weight'            => 'decimal:3',
        'dimensions'        => 'array',
        'attributes'        => 'array',
        'is_active'         => 'boolean',
        'is_trackable'      => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // -------------------------------------------------------------------------
    // Domain behaviour
    // -------------------------------------------------------------------------

    /**
     * Return the available (uncommitted) stock for this product.
     */
    public function availableStock(): int
    {
        return max(0, $this->stock_quantity - $this->reserved_quantity);
    }

    /**
     * Determine whether this product has sufficient stock for a given quantity.
     */
    public function hasSufficientStock(int $quantity): bool
    {
        return $this->availableStock() >= $quantity;
    }

    /**
     * Return true when stock is at or below the reorder point.
     */
    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->reorder_point;
    }

    /**
     * Reserve a quantity of stock (increments reserved_quantity).
     *
     * @throws \UnderflowException When available stock is insufficient.
     */
    public function reserve(int $quantity): void
    {
        if (!$this->hasSufficientStock($quantity)) {
            throw new \UnderflowException(
                "Insufficient available stock for product {$this->sku}. "
                . "Available: {$this->availableStock()}, Requested: {$quantity}"
            );
        }

        $this->reserved_quantity += $quantity;
        $this->save();
    }

    /**
     * Release previously reserved stock back to available.
     */
    public function release(int $quantity): void
    {
        $this->reserved_quantity = max(0, $this->reserved_quantity - $quantity);
        $this->save();
    }

    /**
     * Commit reserved stock (deduct from both stock and reserved).
     */
    public function commitReservation(int $quantity): void
    {
        $this->stock_quantity    = max(0, $this->stock_quantity - $quantity);
        $this->reserved_quantity = max(0, $this->reserved_quantity - $quantity);
        $this->save();
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereColumn('stock_quantity', '<=', 'reorder_point');
    }

    public function scopeForTenant(\Illuminate\Database\Eloquent\Builder $query, int|string $tenantId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('tenant_id', $tenantId);
    }
}
