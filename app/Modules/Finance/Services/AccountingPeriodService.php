<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Finance\DTOs\AccountingPeriodData;
use Modules\Finance\Enums\AccountingPeriodEventType;
use Modules\Finance\Enums\AccountingPeriodStatus;
use Modules\Finance\Models\FinanceAccountingPeriod;
use Modules\Finance\Models\FinanceAccountingPeriodEvent;

final class AccountingPeriodService
{
    private const CODE_PATTERN = '/^[A-Z0-9][A-Z0-9._-]*$/';

    public function create(AccountingPeriodData $data): FinanceAccountingPeriod
    {
        $code = strtoupper(trim($data->code));
        $name = trim($data->name);
        if ($code === '' || preg_match(self::CODE_PATTERN, $code) !== 1) {
            throw new InvalidArgumentException('Accounting period code is invalid.');
        }
        if ($name === '') {
            throw new InvalidArgumentException('Accounting period name is required.');
        }
        $this->assertDate($data->startDate, 'Accounting period start date');
        $this->assertDate($data->endDate, 'Accounting period end date');
        if ($data->endDate < $data->startDate) {
            throw new InvalidArgumentException('Accounting period end date cannot be before its start date.');
        }

        return DB::transaction(function () use ($data, $code, $name): FinanceAccountingPeriod {
            $scopeKey = $this->scopeKey($data->organizationUnitId);
            $overlap = FinanceAccountingPeriod::query()
                ->where('tenant_id', $data->tenantId)
                ->where('organization_scope_key', $scopeKey)
                ->whereDate('start_date', '<=', $data->endDate)
                ->whereDate('end_date', '>=', $data->startDate)
                ->lockForUpdate()
                ->exists();
            if ($overlap) {
                throw new InvalidArgumentException('Accounting period dates overlap an existing period in this scope.');
            }

            $period = new FinanceAccountingPeriod();
            $period->forceFill([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'organization_scope_key' => $scopeKey,
                'code' => $code,
                'name' => $name,
                'start_date' => $data->startDate,
                'end_date' => $data->endDate,
                'status' => AccountingPeriodStatus::Open->value,
                'row_version' => 1,
                'created_by' => $data->createdBy,
            ])->save();
            $this->record(
                $period,
                AccountingPeriodEventType::Created,
                null,
                AccountingPeriodStatus::Open,
                $data->createdBy,
                null,
            );

            return $period->refresh()->load('events');
        });
    }

    public function close(
        FinanceAccountingPeriod $period,
        int $expectedVersion,
        string $reason,
        ?int $actorId = null,
    ): FinanceAccountingPeriod {
        return $this->transition(
            $period,
            $expectedVersion,
            AccountingPeriodStatus::Closed,
            AccountingPeriodEventType::Closed,
            $reason,
            $actorId,
        );
    }

    public function reopen(
        FinanceAccountingPeriod $period,
        int $expectedVersion,
        string $reason,
        ?int $actorId = null,
    ): FinanceAccountingPeriod {
        return $this->transition(
            $period,
            $expectedVersion,
            AccountingPeriodStatus::Open,
            AccountingPeriodEventType::Reopened,
            $reason,
            $actorId,
        );
    }

    public function assertPostingDateAllowed(
        int $tenantId,
        ?int $organizationUnitId,
        string $postingDate,
    ): void {
        $this->assertDate($postingDate, 'Finance posting date');
        $scopeKey = $this->configuredScopeKey($tenantId, $organizationUnitId);
        if ($scopeKey === null) {
            return;
        }

        $period = FinanceAccountingPeriod::query()
            ->where('tenant_id', $tenantId)
            ->where('organization_scope_key', $scopeKey)
            ->whereDate('start_date', '<=', $postingDate)
            ->whereDate('end_date', '>=', $postingDate)
            ->sharedLock()
            ->first();
        if (! $period instanceof FinanceAccountingPeriod) {
            throw new InvalidArgumentException(
                "Finance posting date [{$postingDate}] is outside the configured accounting periods for this scope.",
            );
        }
        $status = $this->status($period);
        if ($status !== AccountingPeriodStatus::Open) {
            throw new InvalidArgumentException(
                "Finance posting date [{$postingDate}] belongs to closed accounting period [{$period->code}].",
            );
        }
    }

    public function scopeQuery(
        int $tenantId,
        ?int $organizationUnitId,
    ): Builder {
        return FinanceAccountingPeriod::query()
            ->where('tenant_id', $tenantId)
            ->where('organization_scope_key', $this->scopeKey($organizationUnitId));
    }

    private function transition(
        FinanceAccountingPeriod $period,
        int $expectedVersion,
        AccountingPeriodStatus $to,
        AccountingPeriodEventType $eventType,
        string $reason,
        ?int $actorId,
    ): FinanceAccountingPeriod {
        if ($expectedVersion < 1) {
            throw new InvalidArgumentException('Expected accounting period version must be positive.');
        }
        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('Accounting period lifecycle reason is required.');
        }

        return DB::transaction(function () use ($period, $expectedVersion, $to, $eventType, $reason, $actorId): FinanceAccountingPeriod {
            $locked = FinanceAccountingPeriod::query()
                ->lockForUpdate()
                ->findOrFail($period->getKey());
            if ((int) $locked->row_version !== $expectedVersion) {
                throw new InvalidArgumentException(
                    'Accounting period was changed by another request. Reload it before continuing.',
                );
            }
            $from = $this->status($locked);
            if ($from === $to) {
                throw new InvalidArgumentException(
                    "Accounting period is already {$to->value}.",
                );
            }

            $locked->forceFill([
                'status' => $to->value,
                'row_version' => $expectedVersion + 1,
            ])->save();
            $this->record($locked, $eventType, $from, $to, $actorId, $reason);

            return $locked->refresh()->load('events');
        });
    }

    private function configuredScopeKey(int $tenantId, ?int $organizationUnitId): ?int
    {
        if ($organizationUnitId !== null
            && FinanceAccountingPeriod::query()
                ->where('tenant_id', $tenantId)
                ->where('organization_scope_key', $organizationUnitId)
                ->exists()) {
            return $organizationUnitId;
        }
        if (FinanceAccountingPeriod::query()
            ->where('tenant_id', $tenantId)
            ->where('organization_scope_key', FinanceAccountingPeriod::TENANT_SCOPE_KEY)
            ->exists()) {
            return FinanceAccountingPeriod::TENANT_SCOPE_KEY;
        }

        return null;
    }

    private function scopeKey(?int $organizationUnitId): int
    {
        return $organizationUnitId ?? FinanceAccountingPeriod::TENANT_SCOPE_KEY;
    }

    private function record(
        FinanceAccountingPeriod $period,
        AccountingPeriodEventType $eventType,
        ?AccountingPeriodStatus $from,
        AccountingPeriodStatus $to,
        ?int $actorId,
        ?string $reason,
    ): void {
        $event = new FinanceAccountingPeriodEvent();
        $event->forceFill([
            'tenant_id' => (int) $period->tenant_id,
            'organization_unit_id' => $period->organization_unit_id,
            'accounting_period_id' => (int) $period->getKey(),
            'event_type' => $eventType->value,
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'reason' => $reason,
            'actor_id' => $actorId,
            'occurred_at' => now(),
        ])->save();
    }

    private function status(FinanceAccountingPeriod $period): AccountingPeriodStatus
    {
        return $period->status instanceof AccountingPeriodStatus
            ? $period->status
            : AccountingPeriodStatus::from((string) $period->status);
    }

    private function assertDate(string $date, string $field): void
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        $errors = DateTimeImmutable::getLastErrors();
        if ($parsed === false
            || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $parsed->format('Y-m-d') !== $date) {
            throw new InvalidArgumentException($field.' must use a valid YYYY-MM-DD value.');
        }
    }
}
