<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Audit\Infrastructure\Persistence\Eloquent\Traits\HasAudit;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\CurrencyModel;
use Modules\Core\Infrastructure\Persistence\Eloquent\Models\BaseModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Traits\HasTenant;

class JournalEntryLineModel extends BaseModel
{
    use HasAudit;
    use HasTenant;

    protected $table = 'journal_entry_lines';

    protected $fillable = [
        'tenant_id',
        'org_unit_id',
        'row_version',
        'journal_entry_id',
        'account_id',
        'description',
        'debit_amount',
        'credit_amount',
        'currency_id',
        'exchange_rate',
        'base_debit_amount',
        'base_credit_amount',
        'cost_center_id',
        'metadata',
    ];

    protected $casts = [
        'debit_amount' => 'decimal:6',
        'credit_amount' => 'decimal:6',
        'exchange_rate' => 'decimal:10',
        'base_debit_amount' => 'decimal:6',
        'base_credit_amount' => 'decimal:6',
        'metadata' => 'array',
    ];

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntryModel::class, 'journal_entry_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'account_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class, 'currency_id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenterModel::class, 'cost_center_id');
    }
}
