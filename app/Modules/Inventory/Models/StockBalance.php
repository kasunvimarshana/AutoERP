<?php

namespace App\Modules\Inventory\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockBalance extends BaseModel
{
    protected $table = 'stock_balances';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'variant_id',
        'batch_id',
        'location_id',
        'uom_id',
        'qty_on_hand',
        'qty_reserved',
        'qty_available',
        'qty_incoming',
        'avg_cost',
        'updated_at'
    ];

    protected $casts = [
        'qty_on_hand' => 'decimal:4',
        'qty_reserved' => 'decimal:4',
        'qty_available' => 'decimal:4',
        'qty_incoming' => 'decimal:4',
        'avg_cost' => 'decimal:4',
        'updated_at' => 'datetime'
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

    public function uom(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Product\Models\UnitOfMeasure::class, 'uom_id');
    }
}
