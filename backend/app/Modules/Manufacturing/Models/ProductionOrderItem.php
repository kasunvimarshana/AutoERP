<?php

namespace App\Modules\Manufacturing\Models;

use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\UnitOfMeasure;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrderItem extends Model
{
    use HasFactory, TenantScoped;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\Manufacturing\ProductionOrderItemFactory::new();
    }

    protected $fillable = [
        'tenant_id',
        'production_order_id',
        'product_id',
        'planned_quantity',
        'consumed_quantity',
        'uom_id',
        'notes',
    ];

    protected $casts = [
        'planned_quantity' => 'decimal:4',
        'consumed_quantity' => 'decimal:4',
    ];

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class);
    }

    /**
     * Check if item is fully consumed
     */
    public function isFullyConsumed(): bool
    {
        return $this->consumed_quantity >= $this->planned_quantity;
    }

    /**
     * Get remaining quantity to consume
     */
    public function getRemainingQuantity(): float
    {
        return max(0, $this->planned_quantity - $this->consumed_quantity);
    }
}
