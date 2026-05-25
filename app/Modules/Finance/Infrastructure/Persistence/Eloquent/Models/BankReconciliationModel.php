<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasStatusScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\BankAccountModel;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;

class BankReconciliationModel extends Model
{
    use HasTenantScope, HasOrganizationUnitScope, HasStatusScope;

    protected $table = 'bank_reconciliations';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'closing_balance' => 'decimal:4',
            'completed_at' => 'datetime',
            'difference' => 'decimal:4',
            'metadata' => 'array',
            'opening_balance' => 'decimal:4',
            'period_end' => 'date',
            'period_start' => 'date',
            'row_version' => 'integer',
            'statement_balance' => 'decimal:4',
        ];
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'approved_by');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccountModel::class, 'bank_account_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'completed_by');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

}
