<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * InventoryItem model – tracks stock levels for a product.
 *
 * @property string  $id
 * @property string  $product_id
 * @property string  $tenant_id
 * @property int     $quantity_available
 * @property int     $quantity_reserved
 * @property int     $reorder_threshold
 * @property string  $warehouse_location
 */
class InventoryItem extends Model
{
    use HasFactory, HasUuids;

    /** @var array<string> */
    protected $fillable = [
        'product_id',
        'tenant_id',
        'quantity_available',
        'quantity_reserved',
        'reorder_threshold',
        'warehouse_location',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'quantity_available' => 'integer',
        'quantity_reserved'  => 'integer',
        'reorder_threshold'  => 'integer',
    ];

    /**
     * Product this inventory item belongs to.
     *
     * @return BelongsTo<Product, InventoryItem>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
