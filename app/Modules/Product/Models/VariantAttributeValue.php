<?php

namespace App\Modules\Product\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class VariantAttributeValue extends BaseModel
{
    protected $table = 'variant_attribute_values';

    protected $fillable = [
        'variant_id',
        'attribute_value_id'
    ];

    protected $casts = [
        
    ];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Product\Models\ProductVariant::class, 'variant_id');
    }

    public function attributeValue(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Product\Models\ProductAttributeValue::class, 'attribute_value_id');
    }
}
