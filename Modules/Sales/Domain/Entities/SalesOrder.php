<?php

declare(strict_types=1);

namespace Modules\Sales\Domain\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Domain\Traits\HasTenant;

/**
 * SalesOrder entity.
 *
 * All monetary amounts are cast to string to enforce BCMath precision.
 */
class SalesOrder extends Model
{
    use HasTenant;

    protected $table = 'sales_orders';

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'order_number',
        'status',
        'order_date',
        'currency_code',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'notes',
        'warehouse_id',
    ];

    protected $casts = [
        'order_date'      => 'date',
        'subtotal'        => 'string',
        'discount_amount' => 'string',
        'tax_amount'      => 'string',
        'total_amount'    => 'string',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesOrderLine::class, 'sales_order_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(SalesDelivery::class, 'sales_order_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class, 'sales_order_id');
    }
}
