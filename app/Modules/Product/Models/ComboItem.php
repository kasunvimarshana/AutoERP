<?php

namespace App\Modules\Product\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ComboItem extends BaseModel
{
    protected $table = 'combo_items';

    protected $fillable = [
        'parent_product_id',
        'child_product_id',
        'child_variant_id',
        'quantity',
        'uom_id'
    ];

    protected $casts = [
        'quantity' => 'decimal:4'
    ];

    public function parentProduct(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Product\Models\Product::class, 'parent_product_id');
    }

    public function childProduct(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Product\Models\Product::class, 'child_product_id');
    }

    public function childVariant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Product\Models\ProductVariant::class, 'child_variant_id');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Product\Models\UnitOfMeasure::class, 'uom_id');
    }
}
