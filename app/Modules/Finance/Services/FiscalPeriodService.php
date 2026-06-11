<?php

declare(strict_types=1);

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Finance\Enums\FiscalPeriodStatus;
use Modules\Finance\Models\FinanceFiscalPeriod;
use Modules\Finance\Models\FinanceFiscalYear;

final class FiscalPeriodService
{
    public function resolve(
        int $tenantId,
        ?int $organizationUnitId,
        string $postingDate,
        ?int $fiscalPeriodId = null,
        bool $requireOpen = false,
    ): FinanceFiscalPeriod {
        $query = FinanceFiscalPeriod::query()
            ->with('fiscalYear')
            ->where('tenant_id', $tenantId);

        $organizationUnitId === null
            ? $query->whereNull('organization_unit_id')
            : $query->where('organization_unit_id', $organizationUnitId);

        if ($fiscalPeriodId !== null) {
            $query->whereKey($fiscalPeriodId);
        } else {
            $query
                ->whereDate('start_date', '<=', $postingDate)
                ->whereDate('end_date', '>=', $postingDate);
        }

        $period = $query->first();
        if (! $period instanceof FinanceFiscalPeriod) {
            throw new InvalidArgumentException('No fiscal period exists for the posting date and scope.');
        }

        if ($postingDate < $period->start_date->toDateString() || $postingDate > $period->end_date->toDateString()) {
            throw new InvalidArgumentException('Posting date is outside the selected fiscal period.');
        }

        if ($requireOpen) {
            $this->assertOpen($period);
        }

        return $period;
    }

    public function assertOpen(?FinanceFiscalPeriod $period): void
    {
        if (! $period instanceof FinanceFiscalPeriod) {
            throw new InvalidArgumentException('A fiscal period is required for posting.');
        }

        $status = $period->status instanceof FiscalPeriodStatus
            ? $period->status
            : FiscalPeriodStatus::from((string) $period->status);

        if ($status !== FiscalPeriodStatus::Open) {
            throw new InvalidArgumentException('Cannot post into a closed or locked fiscal period.');
        }

        $year = $period->fiscalYear;
        if (! $year instanceof FinanceFiscalYear) {
            throw new InvalidArgumentException('Fiscal period must belong to a fiscal year.');
        }

        $yearStatus = $year->status instanceof FiscalPeriodStatus
            ? $year->status
            : FiscalPeriodStatus::from((string) $year->status);

        if ($yearStatus !== FiscalPeriodStatus::Open) {
            throw new InvalidArgumentException('Cannot post into a locked, closed, or year-closed fiscal year.');
        }
    }

    public function changePeriodStatus(
        FinanceFiscalPeriod $period,
        FiscalPeriodStatus $status,
    ): FinanceFiscalPeriod {
        $yearStatus = $period->fiscalYear->status instanceof FiscalPeriodStatus
            ? $period->fiscalYear->status
            : FiscalPeriodStatus::from((string) $period->fiscalYear->status);

        if ($yearStatus === FiscalPeriodStatus::YearClosed) {
            throw new InvalidArgumentException('Periods in a year-closed fiscal year cannot be reopened.');
        }
        if ($status === FiscalPeriodStatus::YearClosed) {
            throw new InvalidArgumentException('Year-closed status applies to fiscal years.');
        }

        $period->forceFill(['status' => $status->value])->save();

        return $period->refresh();
    }

    public function changeYearStatus(
        FinanceFiscalYear $year,
        FiscalPeriodStatus $status,
    ): FinanceFiscalYear {
        $current = $year->status instanceof FiscalPeriodStatus
            ? $year->status
            : FiscalPeriodStatus::from((string) $year->status);

        if ($current === FiscalPeriodStatus::YearClosed && $status !== FiscalPeriodStatus::YearClosed) {
            throw new InvalidArgumentException('A year-closed fiscal year cannot be reopened.');
        }

        return DB::transaction(function () use ($year, $status): FinanceFiscalYear {
            $year->forceFill(['status' => $status->value])->save();
            if ($status === FiscalPeriodStatus::YearClosed) {
                $year->periods()->update(['status' => FiscalPeriodStatus::YearClosed->value]);
            }

            return $year->refresh();
        });
    }
}
