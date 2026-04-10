<?php

namespace App\Modules\Product\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProductAttributeValue extends BaseModel
{
    protected $table = 'product_attribute_values';

    protected $fillable = [
        'attribute_id',
        'value',
        'sort_order'
    ];

    protected $casts = [
        'sort_order' => 'integer'
    ];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Product\Models\ProductAttribute::class, 'attribute_id');
    }
}
