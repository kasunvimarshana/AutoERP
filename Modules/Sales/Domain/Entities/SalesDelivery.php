<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * SalesDelivery entity.
 */
class SalesDelivery extends Model
{
    use HasTenant;

    protected $table = 'sales_deliveries';

    protected $fillable = [
        'tenant_id',
        'sales_order_id',
        'delivery_number',
        'status',
        'shipped_at',
        'delivered_at',
    ];

    protected $casts = [
        'shipped_at'   => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }
}
