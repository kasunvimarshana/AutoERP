<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\GdnHeader;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesOrderLine;
use Modules\Sales\Domain\Enums\InvoiceStatus;
use Modules\Sales\Domain\Enums\SalesOrderStatus;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class SalesOrder extends Model
{
    use HasTenantAndOrganization;
    use SoftDeletes;

    protected $table = 'sales_orders';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'status' => SalesOrderStatus::class,
            'invoice_status' => InvoiceStatus::class,
            'exchange_rate' => 'decimal:4',
            'order_date' => 'date',
            'requested_delivery_date' => 'date',
            'subtotal' => 'decimal:4',
            'line_tax_total' => 'decimal:4',
            'line_discount_total' => 'decimal:4',
            'header_discount_value' => 'decimal:4',
            'header_discount_amount' => 'decimal:4',
            'header_tax_amount' => 'decimal:4',
            'discount_total' => 'decimal:4',
            'tax_total' => 'decimal:4',
            'debit_note_total' => 'decimal:4',
            'credit_note_total' => 'decimal:4',
            'grand_total' => 'decimal:4',
            'paid_amount' => 'decimal:4',
            'balance' => 'decimal:4',
        ];
    }

    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereIn('status', [SalesOrderStatus::Draft, SalesOrderStatus::Confirmed]);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Customer\\Infrastructure\\Persistence\\Eloquent\\Models\\Customer',
            'customer_id'
        );
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Warehouse\\Infrastructure\\Persistence\\Eloquent\\Models\\Warehouse',
            'warehouse_id'
        );
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Models\\Currency',
            'currency_id'
        );
    }

    public function priceList(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Pricing\\Infrastructure\\Persistence\\Eloquent\\Models\\PriceList',
            'price_list_id'
        );
    }

    public function headerTaxGroup(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Models\\TaxGroup',
            'header_tax_group_id'
        );
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesOrderLine::class, 'sales_order_id');
    }

    public function gdnHeaders(): HasMany
    {
        return $this->hasMany(GdnHeader::class, 'sales_order_id');
    }
}
