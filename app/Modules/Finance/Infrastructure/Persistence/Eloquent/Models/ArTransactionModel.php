<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Configuration\Infrastructure\Persistence\Eloquent\Models\CurrencyModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class ArTransactionModel extends Model
{
    use HasOrganizationUnitScope, HasTenantScope;

    protected $table = 'ar_transactions';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'account_id' => 'integer',
            'balance_after' => 'decimal:4',
            'credit_amount' => 'decimal:4',
            'currency_id' => 'integer',
            'debit_amount' => 'decimal:4',
            'due_date' => 'date',
            'exchange_rate' => 'decimal:4',
            'is_reconciled' => 'boolean',
            'metadata' => 'array',
            'organization_unit_id' => 'integer',
            'party_id' => 'integer',
            'reference_id' => 'integer',
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'transaction_date' => 'date',
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

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'account_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(CurrencyModel::class, 'currency_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function party(): MorphTo
    {
        return $this->morphTo();
    }
}

