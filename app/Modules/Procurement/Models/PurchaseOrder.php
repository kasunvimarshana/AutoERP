<?php

namespace App\Modules\Procurement\Models;

use BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PurchaseOrder extends BaseModel
{
    protected $table = 'purchase_orders';

    protected $fillable = [
        'tenant_id',
        'period_id',
        'po_number',
        'supplier_id',
        'warehouse_id',
        'status',
        'order_date',
        'expected_date',
        'currency_id',
        'exchange_rate',
        'payment_term_id',
        'tax_inclusive',
        'subtotal',
        'tax_total',
        'discount_total',
        'total',
        'notes',
        'approved_by',
        'created_by',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'order_date' => 'date',
        'exchange_rate' => 'decimal:4',
        'tax_inclusive' => 'boolean',
        'subtotal' => 'decimal:4',
        'tax_total' => 'decimal:4',
        'discount_total' => 'decimal:4',
        'total' => 'decimal:4',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\Tenant::class, 'tenant_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Core\Models\AccountingPeriod::class, 'period_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Identity\Models\Party::class, 'supplier_id');
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
