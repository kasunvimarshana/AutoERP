<?php

namespace App\Models;

/**
 * Product Model
 * 
 * Example model demonstrating the CRUD framework usage.
 * Products support multi-tenancy, variants, and inventory tracking.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string $sku
 * @property string|null $description
 * @property float $price
 * @property string $status
 * @property int|null $category_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class Product extends BaseModel
{
    /**
     * The table associated with the model
     */
    protected $table = 'products';

    /**
     * The attributes that are mass assignable
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'sku',
        'description',
        'price',
        'status',
        'category_id',
    ];

    /**
     * The attributes that should be cast
     */
    protected $casts = [
        'price' => 'decimal:2',
        'status' => 'string',
        'tenant_id' => 'integer',
        'category_id' => 'integer',
    ];

    /**
     * Get the category that owns the product
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the inventory records for the product
     */
    public function inventoryItems()
    {
        return $this->hasMany(InventoryItem::class);
    }

    /**
     * Scope to filter active products
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to filter by category
     */
    public function scopeOfCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }
}
