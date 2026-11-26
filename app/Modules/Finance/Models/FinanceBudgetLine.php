<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;

final class FinanceBudgetLine extends TenantOwnedModel
{
    protected $table = 'finance_budget_lines';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        $assertDraft = static function (self $line): void {
            $budget = $line->budget;
            if ($budget && (string) $budget->status !== 'draft') {
                throw new LogicException('Lines of non-draft budgets cannot be modified or deleted.');
            }
        };

        self::updating($assertDraft);
        self::deleting($assertDraft);
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'budget_id' => 'integer',
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'account_id' => 'integer',
            'dimension_id' => 'integer',
            'budget_month' => 'integer',
            'amount' => 'decimal:6',
        ]);
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(FinanceBudget::class, 'budget_id');
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
        return $this->belongsTo(FinanceAccount::class, 'account_id');
    }

    public function dimension(): BelongsTo
    {
        return $this->belongsTo(FinanceDimension::class, 'dimension_id');
    }
}
