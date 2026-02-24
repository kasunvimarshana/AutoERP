<?php

namespace Modules\ECommerce\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class ECommerceOrderLineModel extends Model
{
    use HasTenantScope;

    protected $table = 'ec_order_lines';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'order_id',
        'product_listing_id',
        'product_name',
        'unit_price',
        'quantity',
        'discount',
        'tax_rate',
        'line_total',
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
