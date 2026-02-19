<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use App\Core\Traits\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Purchase Order Item Model
 *
 * Represents an item in a purchase order
 *
 * @property int $id
 * @property int $purchase_order_id
 * @property int $item_id
 * @property int $quantity
 * @property float $unit_cost
 * @property float $total
 * @property int $received_quantity
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PurchaseOrderItem extends Model
{
    use HasFactory;
    use TenantAware;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'purchase_order_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'purchase_order_id',
        'item_id',
        'quantity',
        'unit_cost',
        'total',
        'received_quantity',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'purchase_order_id' => 'integer',
        'item_id' => 'integer',
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'total' => 'decimal:2',
        'received_quantity' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the purchase order
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    /**
     * Get the inventory item
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    /**
     * Check if item is fully received
     */
    public function isFullyReceived(): bool
    {
        return $this->received_quantity >= $this->quantity;
    }

    /**
     * Get remaining quantity to receive
     */
    public function getRemainingQuantity(): int
    {
        return max(0, $this->quantity - $this->received_quantity);
    }

    /**
     * Boot method to calculate total
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->total = $item->quantity * $item->unit_cost;
        });
    }
}
