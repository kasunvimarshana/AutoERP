<?php

namespace App\Modules\Inventory\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CycleCountLine extends BaseModel
{
    protected $table = 'cycle_count_lines';

    protected $fillable = [
        'cycle_count_id',
        'location_id',
        'product_id',
        'variant_id',
        'batch_id',
        'expected_qty',
        'counted_qty',
        'variance_qty',
        'status',
        'counted_by',
        'counted_at'
    ];

    protected $casts = [
        'expected_qty' => 'decimal:4',
        'counted_qty' => 'decimal:4',
        'variance_qty' => 'decimal:4'
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Warehouse\Models\Location::class, 'location_id');
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
}
