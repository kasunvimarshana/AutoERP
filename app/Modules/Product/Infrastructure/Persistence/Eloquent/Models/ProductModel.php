<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductModel extends Model
{
    use SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'tenant_id',
        'sku',
        'name',
        'description',
        'price',
        'currency',
        'category',
        'status',
        'attributes',
        'metadata',
    ];

    protected $casts = [
        'price'      => 'float',
        'attributes' => 'array',
        'metadata'   => 'array',
    ];

    public function images(): HasMany
    {
        return $this->hasMany(ProductImageModel::class, 'product_id')->orderBy('sort_order');
    }
}
