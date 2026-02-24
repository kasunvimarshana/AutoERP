<?php

namespace Modules\Logistics\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class DeliveryLineModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'logistics_delivery_lines';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'delivery_order_id',
        'product_id',
        'product_name',
        'quantity',
        'unit',
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
