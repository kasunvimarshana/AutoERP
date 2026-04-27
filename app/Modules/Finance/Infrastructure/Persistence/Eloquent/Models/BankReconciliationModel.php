<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class BankReconciliationModel extends BaseModel
{
    use HasAudit;
    use HasTenant;

    protected $table = 'bank_reconciliations';

    protected $fillable = [
        'tenant_id', 'org_unit_id', 'row_version', 'bank_account_id', 'period_start',
        'period_end', 'opening_balance', 'closing_balance', 'status', 'completed_by', 'completed_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'opening_balance' => 'decimal:6',
        'closing_balance' => 'decimal:6',
        'completed_at' => 'datetime',
    ];
}
