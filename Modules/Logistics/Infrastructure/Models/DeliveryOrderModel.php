<?php

namespace Modules\Logistics\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Shared\Infrastructure\Traits\HasTenantScope;

class DeliveryOrderModel extends Model
{
    use HasTenantScope, SoftDeletes;

    protected $table = 'logistics_delivery_orders';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'tenant_id',
        'carrier_id',
        'source_location_id',
        'reference_no',
        'origin_address',
        'destination_address',
        'scheduled_date',
        'delivered_date',
        'status',
        'weight',
        'shipping_cost',
        'notes',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'delivered_date' => 'date',
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
        return $this->hasMany(DeliveryLineModel::class, 'delivery_order_id');
    }

    public function trackingEvents(): HasMany
    {
        return $this->hasMany(TrackingEventModel::class, 'delivery_order_id');
    }
}
