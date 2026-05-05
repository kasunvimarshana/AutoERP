<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class ApTransactionModel extends BaseModel
{
    use HasAudit;
    use HasTenant;

    protected $table = 'ap_transactions';

    protected $fillable = [
        'tenant_id', 'supplier_id', 'account_id', 'transaction_type',
        'reference_type', 'reference_id', 'amount', 'balance_after',
        'transaction_date', 'due_date', 'currency_id', 'is_reconciled',
    ];

    protected $casts = [
        'amount' => 'decimal:6',
        'balance_after' => 'decimal:6',
        'transaction_date' => 'date',
        'due_date' => 'date',
        'is_reconciled' => 'boolean',
    ];
}
