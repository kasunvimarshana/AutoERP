<?php

declare(strict_types=1);

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Finance\Enums\AccountingPeriodStatus;

final class FinanceAccountingPeriod extends TenantOwnedModel
{
    public const TENANT_SCOPE_KEY = 0;

    protected $table = 'finance_accounting_periods';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'organization_scope_key' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => AccountingPeriodStatus::class,
            'row_version' => 'integer',
            'created_by' => 'integer',
        ]);
    }

    public function events(): HasMany
    {
        return $this->hasMany(FinanceAccountingPeriodEvent::class, 'accounting_period_id')
            ->orderBy('occurred_at')
            ->orderBy('id');
    }

    protected static function booted(): void
    {
        self::deleting(static function (): void {
            throw new LogicException('Accounting periods are financial control records and cannot be deleted.');
        });
    }
}
