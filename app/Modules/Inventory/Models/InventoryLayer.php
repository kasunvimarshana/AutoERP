<?php

namespace App\Modules\Inventory\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryLayer extends BaseModel
{
    protected $table = 'inventory_layers';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'variant_id',
        'batch_id',
        'location_id',
        'receipt_date',
        'unit_cost',
        'qty_received',
        'qty_remaining',
        'method',
        'source_type',
        'source_id',
        'is_exhausted',
        'created_at'
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'unit_cost' => 'decimal:4',
        'qty_received' => 'decimal:4',
        'qty_remaining' => 'decimal:4',
        'is_exhausted' => 'boolean',
        'created_at' => 'datetime'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Tenant::class, 'tenant_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Product\Models\Product::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Product\Models\ProductVariant::class, 'variant_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Inventory\Models\Batch::class, 'batch_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Warehouse\Models\Location::class, 'location_id');
    }
}
