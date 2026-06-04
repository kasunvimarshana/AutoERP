<?php

declare(strict_types=1);

namespace Modules\Invoice\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\CoreModel;

final class InvoiceModel extends CoreModel
{
    use SoftDeletes;

    protected $table = 'invoices';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'row_version' => 'integer',
            'organization_unit_id' => 'integer',
            'invoice_type_id' => 'integer',
            'invoice_date' => 'date',
            'due_date' => 'date',
            'party_id' => 'integer',
            'billing_party_id' => 'integer',
            'exchange_rate' => 'decimal:4',
            'subtotal_amount' => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'tax_amount' => 'decimal:4',
            'charge_amount' => 'decimal:4',
            'rounding_amount' => 'decimal:4',
            'total_amount' => 'decimal:4',
            'paid_amount' => 'decimal:4',
            'credited_amount' => 'decimal:4',
            'balance_amount' => 'decimal:4',
            'source_id' => 'integer',
            'source_context' => 'array',
            'schema_version' => 'integer',
            'data_json' => 'array',
            'metadata_json' => 'array',
            'posted_at' => 'datetime',
            'posted_by' => 'integer',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ]);
    }

    public function invoiceType(): BelongsTo
    {
        return $this->belongsTo(InvoiceTypeModel::class, 'invoice_type_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLineModel::class, 'invoice_id');
    }

    public function sourceDocuments(): HasMany
    {
        return $this->hasMany(InvoiceSourceDocumentModel::class, 'invoice_id');
    }

    public function lineTaxes(): HasMany
    {
        return $this->hasMany(InvoiceLineTaxModel::class, 'invoice_id');
    }

    public function charges(): HasMany
    {
        return $this->hasMany(InvoiceChargeModel::class, 'invoice_id');
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(InvoiceDiscountModel::class, 'invoice_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(InvoiceAllocationModel::class, 'invoice_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(InvoiceDocumentModel::class, 'invoice_id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(InvoiceStatusHistoryModel::class, 'invoice_id');
    }

    public function links(): HasMany
    {
        return $this->hasMany(InvoiceLinkModel::class, 'invoice_id');
    }

    public function linkedBy(): HasMany
    {
        return $this->hasMany(InvoiceLinkModel::class, 'linked_invoice_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(InvoiceNoteModel::class, 'invoice_id');
    }
}
