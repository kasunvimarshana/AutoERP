<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Traits\BelongsToTenant;

class StorefrontProductModel extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'storefront_products';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'slug',
        'name',
        'description',
        'price',
        'currency',
        'is_active',
        'is_featured',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
    ];
}
