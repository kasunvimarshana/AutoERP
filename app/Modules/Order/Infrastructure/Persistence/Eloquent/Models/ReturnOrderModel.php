<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasTenant;
use Modules\Core\Infrastructure\Persistence\Eloquent\Traits\HasUuid;

class ReturnOrderModel extends BaseModel
{
    use HasUuid, HasTenant;

    protected $table = 'return_orders';

    protected $fillable = [
        'tenant_id', 'return_number', 'return_date', 'type',
        'source_order_type', 'source_order_id', 'status', 'currency_code',
        'subtotal', 'tax_amount', 'restocking_fee', 'refund_amount',
        'reason', 'notes', 'resolution', 'credit_memo_id', 'warehouse_id', 'created_by', 'metadata',
    ];

    protected $casts = [
        'return_date'    => 'date',
        'subtotal'       => 'decimal:4',
        'tax_amount'     => 'decimal:4',
        'restocking_fee' => 'decimal:4',
        'refund_amount'  => 'decimal:4',
        'metadata'       => 'array',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
        'deleted_at'     => 'datetime',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(ReturnOrderLineModel::class, 'return_order_id');
    }
}
