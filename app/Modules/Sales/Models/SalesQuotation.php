<?php

declare(strict_types=1);

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Customer\Models\Customer;
use Modules\Sales\Enums\SalesQuotationStatus;

final class SalesQuotation extends TenantOwnedModel
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'quotation_date' => 'date',
            'valid_until' => 'date',
            'status' => SalesQuotationStatus::class,
            'exchange_rate' => 'decimal:6',
            'subtotal' => 'decimal:6',
            'line_discount_total' => 'decimal:6',
            'line_tax_total' => 'decimal:6',
            'line_charge_total' => 'decimal:6',
            'header_increase_total' => 'decimal:6',
            'header_decrease_total' => 'decimal:6',
            'grand_total' => 'decimal:6',
            'approved_at' => 'datetime',
        ]);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesQuotationLine::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(SalesHeaderAdjustment::class, 'source_id')
            ->where('source_type', 'sales_quotation');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(SalesOrder::class, 'quotation_id');
    }
}
