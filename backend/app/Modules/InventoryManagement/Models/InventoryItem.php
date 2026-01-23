<?php

namespace App\Modules\InventoryManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class InventoryItem extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\InventoryItemFactory::new();
    }

    protected $fillable = [
        'tenant_id',
        'sku',
        'part_number',
        'name',
        'description',
        'item_type',
        'category',
        'brand',
        'manufacturer',
        'unit_of_measure',
        'cost_price',
        'selling_price',
        'markup_percentage',
        'quantity_in_stock',
        'minimum_stock_level',
        'reorder_quantity',
        'location',
        'is_taxable',
        'is_active',
        'is_dummy',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'markup_percentage' => 'decimal:2',
        'quantity_in_stock' => 'integer',
        'minimum_stock_level' => 'integer',
        'reorder_quantity' => 'integer',
        'is_taxable' => 'boolean',
        'is_active' => 'boolean',
        'is_dummy' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            if (empty($item->uuid)) {
                $item->uuid = Str::uuid();
            }
            if (empty($item->sku)) {
                $item->sku = static::generateSku();
            }
        });
    }

    /**
     * Generate unique SKU
     */
    protected static function generateSku(): string
    {
        do {
            $code = 'SKU-' . strtoupper(Str::random(8));
        } while (static::where('sku', $code)->exists());

        return $code;
    }

    /**
     * Get the tenant that owns the inventory item
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    /**
     * Get the stock movements for the inventory item
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Get the purchase order items for the inventory item
     */
    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * Get full display name attribute
     */
    public function getFullNameAttribute(): string
    {
        return $this->part_number ? "{$this->name} ({$this->part_number})" : $this->name;
    }

    /**
     * Check if item is low stock
     */
    public function getIsLowStockAttribute(): bool
    {
        if ($this->minimum_stock_level === null) {
            return false;
        }

        return $this->quantity_in_stock <= $this->minimum_stock_level;
    }

    /**
     * Check if item is out of stock
     */
    public function getIsOutOfStockAttribute(): bool
    {
        return $this->quantity_in_stock <= 0;
    }

    /**
     * Activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Scope: Active items
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: By tenant
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: By item type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('item_type', $type);
    }

    /**
     * Scope: Low stock items
     */
    public function scopeLowStock($query)
    {
        return $query->whereNotNull('minimum_stock_level')
            ->whereColumn('quantity_in_stock', '<=', 'minimum_stock_level');
    }

    /**
     * Scope: Out of stock items
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('quantity_in_stock', '<=', 0);
    }

    /**
     * Scope: Dummy items
     */
    public function scopeDummy($query)
    {
        return $query->where('is_dummy', true);
    }

    /**
     * Scope: Real items (not dummy)
     */
    public function scopeReal($query)
    {
        return $query->where('is_dummy', false);
    }
}
