<?php

namespace App\Modules\InventoryManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PurchaseOrderItem extends Model
{
    use HasFactory, LogsActivity;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\PurchaseOrderItemFactory::new();
    }

    protected $fillable = [
        'purchase_order_id',
        'inventory_item_id',
        'quantity_ordered',
        'quantity_received',
        'unit_price',
        'line_total',
        'notes',
    ];

    protected $casts = [
        'quantity_ordered' => 'integer',
        'quantity_received' => 'integer',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    /**
     * Get the purchase order for the item
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Get the inventory item
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Get quantity remaining to receive
     */
    public function getQuantityRemainingAttribute(): int
    {
        return $this->quantity_ordered - $this->quantity_received;
    }

    /**
     * Check if item is fully received
     */
    public function getIsFullyReceivedAttribute(): bool
    {
        return $this->quantity_received >= $this->quantity_ordered;
    }

    /**
     * Check if item is partially received
     */
    public function getIsPartiallyReceivedAttribute(): bool
    {
        return $this->quantity_received > 0 && $this->quantity_received < $this->quantity_ordered;
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
     * Scope: Fully received items
     */
    public function scopeFullyReceived($query)
    {
        return $query->whereColumn('quantity_received', '>=', 'quantity_ordered');
    }

    /**
     * Scope: Partially received items
     */
    public function scopePartiallyReceived($query)
    {
        return $query->where('quantity_received', '>', 0)
            ->whereColumn('quantity_received', '<', 'quantity_ordered');
    }

    /**
     * Scope: Not received items
     */
    public function scopeNotReceived($query)
    {
        return $query->where('quantity_received', 0);
    }
}
