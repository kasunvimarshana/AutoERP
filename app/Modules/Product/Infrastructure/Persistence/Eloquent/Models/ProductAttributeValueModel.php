<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAttributeValueModel extends Model
{
    protected $table = 'product_attribute_values';

    protected $fillable = ['attribute_id', 'value', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(ProductAttributeModel::class, 'attribute_id');
    }
}
