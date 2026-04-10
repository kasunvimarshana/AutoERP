<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Core\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class InventoryBalance extends Model
{
    use HasUuid;
    
    protected $fillable = [
        'product_id', 'variant_id', 'warehouse_id', 'location_id',
        'batch_id', 'quantity_on_hand', 'quantity_reserved',
        'quantity_in_transit', 'quantity_quarantined', 'average_cost',
        'last_movement_at', 'metrics'
    ];
    
    protected $casts = [
        'metrics' => 'array',
        'last_movement_at' => 'datetime',
    ];
    
    public function product()
    {
        return $this->belongsTo(\App\Modules\Product\Models\Product::class);
    }
    
    public function variant()
    {
        return $this->belongsTo(\App\Modules\Product\Models\ProductVariant::class);
    }
    
    public function warehouse()
    {
        return $this->belongsTo(\App\Modules\Warehouse\Models\Warehouse::class);
    }
    
    public function location()
    {
        return $this->belongsTo(\App\Modules\Warehouse\Models\Location::class);
    }
    
    public function batch()
    {
        return $this->belongsTo(\App\Modules\Traceability\Models\Batch::class);
    }
    
    public function getQuantityAvailableAttribute()
    {
        return $this->quantity_on_hand - $this->quantity_reserved;
    }
}