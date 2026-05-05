<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class CreditMemoModel extends BaseModel
{
    use HasAudit;
    use HasTenant;

    protected $table = 'credit_memos';

    protected $fillable = [
        'tenant_id', 'org_unit_id', 'row_version', 'party_id', 'party_type',
        'return_order_id', 'return_order_type', 'credit_memo_number', 'amount',
        'status', 'issued_date', 'applied_to_invoice_id', 'applied_to_invoice_type',
        'notes', 'journal_entry_id',
    ];

    protected $casts = [
        'amount' => 'decimal:6',
        'issued_date' => 'date',
    ];
}
