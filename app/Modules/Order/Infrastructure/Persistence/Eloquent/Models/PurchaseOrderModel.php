<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class PurchaseOrderModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'purchase_orders';

    protected $fillable = [
        'tenant_id', 'order_number', 'order_date', 'expected_date', 'received_date',
        'supplier_id', 'warehouse_id', 'status', 'currency_code', 'exchange_rate',
        'subtotal', 'discount_amount', 'tax_amount', 'shipping_amount',
        'total_amount', 'paid_amount', 'balance_due', 'payment_status',
        'notes', 'internal_notes', 'created_by', 'metadata',
    ];

    protected $casts = [
        'order_date'      => 'date',
        'expected_date'   => 'date',
        'received_date'   => 'date',
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
        return $this->hasMany(PurchaseOrderLineModel::class, 'purchase_order_id');
    }
}
