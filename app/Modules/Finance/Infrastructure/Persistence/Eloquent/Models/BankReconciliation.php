<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganizationScopes;

class BankReconciliation extends Model
{
    use HasTenantAndOrganizationScopes;

    protected $table = 'bank_reconciliations';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'period_start' => 'date',
            'period_end' => 'date',
            'opening_balance' => 'decimal:4',
            'closing_balance' => 'decimal:4',
            'statement_balance' => 'decimal:4',
            'difference' => 'decimal:4',
            'completed_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo('Modules\\Tenant\\Infrastructure\\Persistence\\Eloquent\\Models\\Tenant', 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo('Modules\\OrganizationUnit\\Infrastructure\\Persistence\\Eloquent\\Models\\OrganizationUnit', 'organization_unit_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo('Modules\\User\\Infrastructure\\Persistence\\Eloquent\\Models\\User', 'completed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo('Modules\\User\\Infrastructure\\Persistence\\Eloquent\\Models\\User', 'approved_by');
    }
}
