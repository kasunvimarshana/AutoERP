<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Infrastructure\Traits\BelongsToTenant;

class ProductAttributeModel extends Model
{
    use BelongsToTenant;

    protected $table = 'product_attributes';

    protected $fillable = [
        'product_id',
        'tenant_id',
        'attribute_key',
        'attribute_label',
        'attribute_value',
        'attribute_type',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }
}
