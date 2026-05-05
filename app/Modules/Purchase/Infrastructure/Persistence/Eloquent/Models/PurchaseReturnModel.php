<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class PurchaseReturnModel extends Model
{
    use HasAudit;
    use HasTenant;

    protected $table = 'purchase_returns';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'supplier_id',
        'original_grn_id',
        'original_invoice_id',
        'return_number',
        'status',
        'return_date',
        'return_reason',
        'currency_id',
        'exchange_rate',
        'subtotal',
        'tax_total',
        'grand_total',
        'debit_note_number',
        'journal_entry_id',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'supplier_id' => 'integer',
        'original_grn_id' => 'integer',
        'original_invoice_id' => 'integer',
        'currency_id' => 'integer',
        'journal_entry_id' => 'integer',
        'exchange_rate' => 'decimal:10',
        'subtotal' => 'decimal:6',
        'tax_total' => 'decimal:6',
        'grand_total' => 'decimal:6',
        'return_date' => 'date',
        'metadata' => 'array',
    ];
}
