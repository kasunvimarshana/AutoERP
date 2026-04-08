<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class SalesOrderModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'sales_orders';

    protected $fillable = [
        'tenant_id', 'order_number', 'order_date', 'required_date', 'shipped_date',
        'customer_id', 'status', 'currency_code', 'exchange_rate',
        'subtotal', 'discount_amount', 'tax_amount', 'shipping_amount',
        'total_amount', 'paid_amount', 'balance_due', 'payment_status',
        'payment_terms', 'notes', 'internal_notes', 'created_by', 'metadata',
    ];

    protected $casts = [
        'order_date'      => 'date',
        'required_date'   => 'date',
        'shipped_date'    => 'date',
        'exchange_rate'   => 'decimal:6',
        'subtotal'        => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'tax_amount'      => 'decimal:4',
        'shipping_amount' => 'decimal:4',
        'total_amount'    => 'decimal:4',
        'paid_amount'     => 'decimal:4',
        'balance_due'     => 'decimal:4',
        'metadata'        => 'array',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
        'deleted_at'      => 'datetime',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(SalesOrderLineModel::class, 'sales_order_id');
    }
}
