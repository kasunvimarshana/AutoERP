<?php

namespace App\Modules\Sales\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SalesOrder extends BaseModel
{
    protected $table = 'sales_orders';

    protected $fillable = [
        'tenant_id',
        'period_id',
        'so_number',
        'customer_id',
        'price_list_id',
        'warehouse_id',
        'status',
        'order_date',
        'requested_date',
        'shipped_date',
        'currency_id',
        'exchange_rate',
        'payment_term_id',
        'tax_inclusive',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
        'billing_address_id',
        'shipping_address_id',
        'notes',
        'created_by'
    ];

    protected $casts = [
        'order_date' => 'date',
        'exchange_rate' => 'decimal:4',
        'tax_inclusive' => 'boolean',
        'subtotal' => 'decimal:4',
        'discount_total' => 'decimal:4',
        'tax_total' => 'decimal:4',
        'total' => 'decimal:4'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Tenant::class, 'tenant_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\AccountingPeriod::class, 'period_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Identity\Models\Party::class, 'customer_id');
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Product\Models\PriceList::class, 'price_list_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Warehouse\Models\Warehouse::class, 'warehouse_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Currency::class, 'currency_id');
    }
}
