<?php

declare(strict_types=1);

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Core\Models\TenantOwnedModel;
use Modules\Finance\Enums\AccountingPeriodEventType;
use Modules\Finance\Enums\AccountingPeriodStatus;

final class FinanceAccountingPeriodEvent extends TenantOwnedModel
{
    protected $table = 'finance_accounting_period_events';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tenant_id' => 'integer',
            'organization_unit_id' => 'integer',
            'accounting_period_id' => 'integer',
            'event_type' => AccountingPeriodEventType::class,
            'from_status' => AccountingPeriodStatus::class,
            'to_status' => AccountingPeriodStatus::class,
            'actor_id' => 'integer',
            'occurred_at' => 'immutable_datetime',
        ]);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(FinanceAccountingPeriod::class, 'accounting_period_id');
    }

    protected static function booted(): void
    {
        self::updating(static function (): void {
            throw new LogicException('Accounting period events are immutable.');
        });
        self::deleting(static function (): void {
            throw new LogicException('Accounting period events cannot be deleted.');
        });
    }
}
