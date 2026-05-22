<?php

declare(strict_types=1);

namespace Modules\Invoice\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Invoice\Domain\Enums\InvoiceDirection;
use Modules\Invoice\Domain\Enums\InvoiceDiscountType;
use Modules\Invoice\Domain\Enums\InvoiceStatus;
use Modules\Invoice\Domain\Enums\InvoiceType;
use Modules\Invoice\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganization;

class Invoice extends Model
{
    use HasTenantAndOrganization;
    use SoftDeletes;

    protected $table = 'invoices';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'direction' => InvoiceDirection::class,
            'invoice_type' => InvoiceType::class,
            'status' => InvoiceStatus::class,
            'invoice_date' => 'date',
            'due_date' => 'date',
            'exchange_rate' => 'decimal:4',
            'subtotal' => 'decimal:4',
            'line_tax_total' => 'decimal:4',
            'line_discount_total' => 'decimal:4',
            'header_discount_type' => InvoiceDiscountType::class,
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
    protected function outstanding(Builder $query): void
    {
        $query->where('balance', '>', 0);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(
            'Modules\\Invoice\\Infrastructure\\Persistence\\Eloquent\\Models\\InvoiceLine',
            'invoice_id'
        );
    }

    public function references(): HasMany
    {
        return $this->hasMany(
            'Modules\\Invoice\\Infrastructure\\Persistence\\Eloquent\\Models\\InvoiceReference',
            'invoice_id'
        );
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Configuration\\Infrastructure\\Persistence\\Eloquent\\Models\\Currency',
            'currency_id'
        );
    }

    public function headerTaxGroup(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Models\\TaxGroup',
            'header_tax_group_id'
        );
    }

    public function apAccount(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Models\\Account',
            'ap_account_id'
        );
    }

    public function arAccount(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Models\\Account',
            'ar_account_id'
        );
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Models\\JournalEntry',
            'journal_entry_id'
        );
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\User\\Infrastructure\\Persistence\\Eloquent\\Models\\User',
            'created_by'
        );
    }
}
