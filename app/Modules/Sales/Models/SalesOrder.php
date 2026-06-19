<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Configuration\Models\CurrencyModel;
use Modules\Core\Models\CoreModel;
use Modules\Customer\Models\Customer;
use Modules\Sales\Enums\SalesOrderStatus;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\Warehouse\Models\WarehouseModel;

final class SalesOrder extends CoreModel
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'sales_order_date' => 'date',
            'expected_delivery_date' => 'date',
            'status' => SalesOrderStatus::class,
            'exchange_rate' => 'decimal:6',
            'subtotal' => 'decimal:6',
            'line_discount_total' => 'decimal:6',
            'line_tax_total' => 'decimal:6',
            'line_charge_total' => 'decimal:6',
            'header_increase_total' => 'decimal:6',
            'header_decrease_total' => 'decimal:6',
            'grand_total' => 'decimal:6',
            'allocated_total' => 'decimal:6',
            'delivered_total' => 'decimal:6',
            'invoiced_total' => 'decimal:6',
            'returned_total' => 'decimal:6',
            'approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'closed_at' => 'datetime',
        ]);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(SalesQuotation::class, 'quotation_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(WarehouseModel::class);
    }

    public function warehouseLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocationModel::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesOrderLine::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(SalesHeaderAdjustment::class, 'source_id')
            ->where('source_type', 'sales_order');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(SalesDelivery::class);
    }

    public function invoiceLinks(): HasMany
    {
        return $this->hasMany(SalesInvoiceLink::class, 'source_id')
            ->where('source_type', 'sales_order');
    }
}
