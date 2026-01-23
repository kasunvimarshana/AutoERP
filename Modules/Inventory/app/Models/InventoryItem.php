<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use App\Core\Traits\AuditTrait;
use App\Core\Traits\TenantAware;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Organization\Models\Branch;

/**
 * Inventory Item Model
 *
 * Represents an inventory item in a specific branch
 *
 * @property int $id
 * @property int $branch_id
 * @property string $item_code
 * @property string $item_name
 * @property string|null $category
 * @property string|null $description
 * @property string $unit_of_measure
 * @property int $reorder_level
 * @property int $reorder_quantity
 * @property float $unit_cost
 * @property float $selling_price
 * @property int $stock_on_hand
 * @property bool $is_dummy_item
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class InventoryItem extends Model
{
    use AuditTrait;
    use HasFactory;
    use SoftDeletes;
    use TenantAware;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Modules\Inventory\Database\Factories\InventoryItemFactory::new();
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'inventory_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'branch_id',
        'item_code',
        'item_name',
        'category',
        'description',
        'unit_of_measure',
        'reorder_level',
        'reorder_quantity',
        'unit_cost',
        'selling_price',
        'stock_on_hand',
        'is_dummy_item',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'branch_id' => 'integer',
        'reorder_level' => 'integer',
        'reorder_quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'stock_on_hand' => 'integer',
        'is_dummy_item' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the branch that owns the item
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get stock movements for this item
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'item_id');
    }

    /**
     * Get purchase order items for this item
     */
    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'item_id');
    }

    /**
     * Scope to get low stock items
     */
    public function scopeLowStock($query)
    {
        return $query->whereRaw('stock_on_hand <= reorder_level')
            ->where('is_dummy_item', false);
    }

    /**
     * Scope to filter by branch
     */
    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope to search items
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('item_name', 'like', "%{$search}%")
                ->orWhere('item_code', 'like', "%{$search}%")
                ->orWhere('category', 'like', "%{$search}%");
        });
    }

    /**
     * Check if item needs reorder
     */
    public function needsReorder(): bool
    {
        return ! $this->is_dummy_item && $this->stock_on_hand <= $this->reorder_level;
    }
}
