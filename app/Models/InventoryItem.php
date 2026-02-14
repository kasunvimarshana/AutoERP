<?php

namespace App\Models;

/**
 * InventoryItem Model
 * 
 * Tracks individual inventory items and stock levels.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $product_id
 * @property int $location_id
 * @property int $quantity
 * @property string|null $batch_number
 * @property \Illuminate\Support\Carbon|null $expiry_date
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class InventoryItem extends BaseModel
{
    /**
     * The table associated with the model
     */
    protected $table = 'inventory_items';

    /**
     * The attributes that are mass assignable
     */
    protected $fillable = [
        'tenant_id',
        'product_id',
        'location_id',
        'quantity',
        'batch_number',
        'expiry_date',
    ];

    /**
     * The attributes that should be cast
     */
    protected $casts = [
        'quantity' => 'integer',
        'expiry_date' => 'date',
        'tenant_id' => 'integer',
        'product_id' => 'integer',
        'location_id' => 'integer',
    ];

    /**
     * Get the product that owns this inventory item
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope to filter by low stock
     */
    public function scopeLowStock($query, int $threshold = 10)
    {
        return $query->where('quantity', '<=', $threshold);
    }
}
