<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Concerns\HasTenantAndOrganizationScopes;

class BudgetLine extends Model
{
    use HasTenantAndOrganizationScopes;

    protected $table = 'budget_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'row_version' => 'integer',
            'metadata' => 'array',
            'period_1_amount' => 'decimal:4',
            'period_2_amount' => 'decimal:4',
            'period_3_amount' => 'decimal:4',
            'period_4_amount' => 'decimal:4',
            'period_5_amount' => 'decimal:4',
            'period_6_amount' => 'decimal:4',
            'period_7_amount' => 'decimal:4',
            'period_8_amount' => 'decimal:4',
            'period_9_amount' => 'decimal:4',
            'period_10_amount' => 'decimal:4',
            'period_11_amount' => 'decimal:4',
            'period_12_amount' => 'decimal:4',
            'total_amount' => 'decimal:4',
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

    public function budget(): BelongsTo
    {
        return $this->belongsTo(
            'Modules\\Finance\\Infrastructure\\Persistence\\Eloquent\\Models\\Budget',
            'budget_id'
        );
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }
}
