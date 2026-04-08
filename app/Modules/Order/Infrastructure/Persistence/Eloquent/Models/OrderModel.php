<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class OrderModel extends BaseModel
{
    use HasTenant, SoftDeletes;

    protected $table = 'orders';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'org_unit_id',
        'reference_number',
        'type',
        'supplier_id',
        'customer_id',
        'status',
        'payment_status',
        'order_date',
        'expected_date',
        'confirmed_at',
        'completed_at',
        'currency',
        'exchange_rate',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'shipping_amount',
        'total_amount',
        'warehouse_id',
        'billing_address',
        'shipping_address',
        'notes',
        'internal_notes',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'exchange_rate'    => 'decimal:10',
        'subtotal'         => 'decimal:6',
        'discount_amount'  => 'decimal:6',
        'tax_amount'       => 'decimal:6',
        'shipping_amount'  => 'decimal:6',
        'total_amount'     => 'decimal:6',
        'billing_address'  => 'array',
        'shipping_address' => 'array',
        'metadata'         => 'array',
        'order_date'       => 'date',
        'expected_date'    => 'date',
        'confirmed_at'     => 'datetime',
        'completed_at'     => 'datetime',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
        'deleted_at'       => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(static function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OrderLineModel::class, 'order_id');
    }
}
