<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\CurrencyModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class JournalEntryLineModel extends Model
{
    use HasOrganizationUnitScope, HasTenantScope;

    protected $table = 'journal_entry_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'account_id' => 'integer',
            'base_credit_amount' => 'decimal:4',
            'base_debit_amount' => 'decimal:4',
            'cost_center_id' => 'integer',
            'credit_amount' => 'decimal:4',
            'currency_id' => 'integer',
            'debit_amount' => 'decimal:4',
            'exchange_rate' => 'decimal:4',
            'journal_entry_id' => 'integer',
            'line_number' => 'integer',
            'metadata' => 'array',
            'organization_unit_id' => 'integer',
            'row_version' => 'integer',
            'tax_amount' => 'decimal:4',
            'tax_rate_id' => 'integer',
            'tenant_id' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

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

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRateModel::class, 'tax_rate_id');
    }
}

