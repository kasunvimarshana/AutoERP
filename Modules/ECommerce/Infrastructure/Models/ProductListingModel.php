<?php

namespace Modules\ECommerce\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class ProductListingModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'ec_product_listings';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'inventory_product_id',
        'name',
        'description',
        'price',
        'compare_at_price',
        'sku',
        'is_published',
        'stock_quantity',
        'image_url',
        'tags',
    ];

    protected $casts = [
        'tags'         => 'array',
        'is_published' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }
}
