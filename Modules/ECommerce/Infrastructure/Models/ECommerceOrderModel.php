<?php

namespace Modules\ECommerce\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class ECommerceOrderModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'ec_orders';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'reference_no',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_address',
        'status',
        'subtotal',
        'tax_amount',
        'shipping_cost',
        'total',
        'payment_method',
        'payment_status',
        'notes',
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

    public function lines(): HasMany
    {
        return $this->hasMany(ECommerceOrderLineModel::class, 'order_id');
    }
}
