<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class BankTransactionModel extends BaseModel
{
    use HasAudit;
    use HasTenant;

    protected $table = 'bank_transactions';

    protected $fillable = [
        'tenant_id', 'org_unit_id', 'row_version', 'bank_account_id', 'external_id',
        'transaction_date', 'description', 'amount', 'balance', 'type', 'status',
        'matched_journal_entry_id', 'category_rule_id',
    ];

    protected $casts = [
        'amount' => 'decimal:6',
        'balance' => 'decimal:6',
        'transaction_date' => 'date',
    ];
}
