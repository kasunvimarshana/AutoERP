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
        'type',
        'units_of_measure',
        'attributes',
        'metadata',
    ];

    protected $casts = [
        'price'            => 'float',
        'units_of_measure' => 'array',
        'attributes'       => 'array',
        'metadata'         => 'array',
    ];

    public function images(): HasMany
    {
        return $this->hasMany(ProductImageModel::class, 'product_id')->orderBy('sort_order');
    }

    public function variations(): HasMany
    {
        return $this->hasMany(ProductVariationModel::class, 'product_id')->orderBy('sort_order');
    }

    public function comboItems(): HasMany
    {
        return $this->hasMany(ProductComboItemModel::class, 'product_id')->orderBy('sort_order');
    }
}

