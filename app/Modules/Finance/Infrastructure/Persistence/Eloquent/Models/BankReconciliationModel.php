<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasStatusScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;

class BankReconciliationModel extends Model
{
    use HasOrganizationUnitScope, HasStatusScope, HasTenantScope;

    protected $table = 'bank_reconciliations';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'approved_by' => 'integer',
            'bank_account_id' => 'integer',
            'closing_balance' => 'decimal:4',
            'completed_at' => 'datetime',
            'completed_by' => 'integer',
            'created_by' => 'integer',
            'difference' => 'decimal:4',
            'metadata' => 'array',
            'opening_balance' => 'decimal:4',
            'organization_unit_id' => 'integer',
            'period_end' => 'date',
            'period_start' => 'date',
            'row_version' => 'integer',
            'statement_balance' => 'decimal:4',
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

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccountModel::class, 'bank_account_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'completed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'approved_by');
    }
}

