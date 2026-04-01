<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasAudit;

class ProductVariationModel extends Model
{
    use HasAudit, SoftDeletes;

    protected $table = 'product_variations';

    protected $fillable = [
        'product_id',
        'tenant_id',
        'sku',
        'name',
        'price',
        'currency',
        'attribute_values',
        'status',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'price'            => 'float',
        'attribute_values' => 'array',
        'metadata'         => 'array',
        'sort_order'       => 'integer',
    ];
}
