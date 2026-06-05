<?php

declare(strict_types=1);

namespace Modules\Invoice\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;
use Modules\Invoice\Domain\Enums\InvoiceDirection;
use Modules\Invoice\Domain\Enums\InvoiceStatus;
use Modules\Invoice\Domain\Enums\InvoiceType;

final class Invoice extends CoreModel
{
    use SoftDeletes;

    protected $table = 'invoices';

    protected $fillable = [
        'tenant_id',
        'organization_unit_id',
        'invoice_number',
        'invoice_type',
        'direction',
        'party_type',
        'party_id',
        'party_name',
        'currency_id',
        'exchange_rate',
        'invoice_date',
        'due_date',
        'status',
        'subtotal',
        'discount_total',
        'tax_total',
        'charge_total',
        'adjustment_total',
        'grand_total',
        'paid_amount',
        'balance_amount',
        'notes',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class, 'invoice_id');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(InvoiceAdjustment::class, 'invoice_id');
    }

    public function sourceLinks(): HasMany
    {
        return $this->hasMany(InvoiceSourceLink::class, 'invoice_id');
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'party_id' => 'integer',
            'currency_id' => 'integer',
            'invoice_type' => InvoiceType::class,
            'direction' => InvoiceDirection::class,
            'invoice_date' => 'date',
            'due_date' => 'date',
            'status' => InvoiceStatus::class,
            'exchange_rate' => 'decimal:10',
            'subtotal' => 'decimal:4',
            'discount_total' => 'decimal:4',
            'tax_total' => 'decimal:4',
            'charge_total' => 'decimal:4',
            'adjustment_total' => 'decimal:4',
            'grand_total' => 'decimal:4',
            'paid_amount' => 'decimal:4',
            'balance_amount' => 'decimal:4',
        ]);
    }
}
