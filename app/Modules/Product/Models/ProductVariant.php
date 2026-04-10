<?php

namespace App\Modules\Product\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProductVariant extends BaseModel
{
    protected $table = 'product_variants';

    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'barcode',
        'weight',
        'is_active',
        'created_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Product\Models\Product::class, 'product_id');
    }
}
