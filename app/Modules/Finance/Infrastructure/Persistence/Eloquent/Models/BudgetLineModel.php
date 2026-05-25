<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasOrganizationUnitScope;
use Modules\Core\Infrastructure\Persistence\Eloquent\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\OrganizationUnit\Infrastructure\Persistence\Eloquent\Models\OrganizationUnitModel;
use Modules\Tenant\Infrastructure\Persistence\Eloquent\Models\TenantModel;

class BudgetLineModel extends Model
{
    use HasOrganizationUnitScope, HasTenantScope;

    protected $table = 'budget_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'account_id' => 'integer',
            'budget_id' => 'integer',
            'cost_center_id' => 'integer',
            'metadata' => 'array',
            'organization_unit_id' => 'integer',
            'period_10_amount' => 'decimal:4',
            'period_11_amount' => 'decimal:4',
            'period_12_amount' => 'decimal:4',
            'period_1_amount' => 'decimal:4',
            'period_2_amount' => 'decimal:4',
            'period_3_amount' => 'decimal:4',
            'period_4_amount' => 'decimal:4',
            'period_5_amount' => 'decimal:4',
            'period_6_amount' => 'decimal:4',
            'period_7_amount' => 'decimal:4',
            'period_8_amount' => 'decimal:4',
            'period_9_amount' => 'decimal:4',
            'row_version' => 'integer',
            'tenant_id' => 'integer',
            'total_amount' => 'decimal:4',
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

    public function budget(): BelongsTo
    {
        return $this->belongsTo(BudgetModel::class, 'budget_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccountModel::class, 'account_id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenterModel::class, 'cost_center_id');
    }
}

