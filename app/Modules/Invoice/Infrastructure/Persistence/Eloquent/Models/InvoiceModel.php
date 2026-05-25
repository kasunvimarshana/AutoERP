<?php

declare(strict_types=1);

namespace Modules\Invoice\Infrastructure\Persistence\Eloquent\Models;

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
            'metadata' => 'array',
            'party_id' => 'integer',
            'invoice_date' => 'date',
            'due_date' => 'date',
            'currency_id' => 'integer',
            'exchange_rate' => 'decimal:4',
            'subtotal' => 'decimal:4',
            'line_tax_total' => 'decimal:4',
            'line_discount_total' => 'decimal:4',
            'header_discount_value' => 'decimal:4',
            'header_discount_amount' => 'decimal:4',
            'header_tax_group_id' => 'integer',
            'header_tax_amount' => 'decimal:4',
            'discount_total' => 'decimal:4',
            'tax_total' => 'decimal:4',
            'debit_note_total' => 'decimal:4',
            'credit_note_total' => 'decimal:4',
            'grand_total' => 'decimal:4',
            'paid_amount' => 'decimal:4',
            'balance' => 'decimal:4',
            'ap_account_id' => 'integer',
            'ar_account_id' => 'integer',
            'journal_entry_id' => 'integer',
            'created_by' => 'integer'
        ]);
    }
}