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
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Tenant\Models\TenantModel;

final class FinanceBudget extends TenantOwnedModel
{
    protected $table = 'finance_budgets';

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        self::deleting(function (self $budget): void {
            if ((string) $budget->status !== 'draft') {
                throw new LogicException('Only draft budgets can be deleted.');
            }
        });

        self::updating(function (self $budget): void {
            $originalStatus = (string) $budget->getOriginal('status');
            if ($originalStatus !== 'draft') {
                $allowed = ['status', 'updated_at'];
                if (array_diff(array_keys($budget->getDirty()), $allowed) !== []) {
                    throw new LogicException('Approved or finalized budgets cannot be modified.');
                }
            }
        });
    }

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'budget_year' => 'integer',
        ]);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantModel::class, 'tenant_id');
    }

    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnitModel::class, 'organization_unit_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(FinanceBudgetLine::class, 'budget_id');
    }
}
