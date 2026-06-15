<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Enums\RentalBillingBasis;
use Modules\VehicleRental\Enums\RentalBillingCycle;
use Modules\VehicleRental\Enums\RentalFinancialSide;
use Modules\VehicleRental\Enums\RentalUsageLogStatus;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalBillingPeriod;
use Modules\VehicleRental\Models\RentalUsageLog;

final class RentalBillingPeriodService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function eligiblePeriods(RentalAgreement $agreement, ?CarbonImmutable $asOf = null): Collection
    {
        $agreement->loadMissing(['rateSnapshot', 'usageContexts.usageLog']);
        $timezone = (string) ($agreement->billing_timezone ?: 'UTC');
        $asOf ??= CarbonImmutable::now($timezone);
        $start = CarbonImmutable::parse($agreement->start_at, $timezone);
        $contractEnd = CarbonImmutable::parse($agreement->actual_end_at ?? $agreement->expected_end_at, $timezone);
        $closedThrough = $this->closedThrough($agreement, $contractEnd, $asOf);
        if ($closedThrough->lessThanOrEqualTo($start) || $agreement->rateSnapshot === null) {
            return collect();
        }

        if ($agreement->billing_cycle === RentalBillingCycle::PerTrip) {
            return $this->perTripPeriods($agreement, $asOf);
        }

        if ($agreement->billing_cycle === RentalBillingCycle::Final) {
            if (! $this->finalEligible($agreement, $contractEnd, $asOf)) {
                return collect();
            }

            return collect([$this->period($agreement, $start, $contractEnd, 1, true)]);
        }

        $periods = collect();
        $cursor = $start;
        $sequence = 1;
        while ($cursor->lessThan($closedThrough)) {
            $next = $this->nextBoundary($agreement, $cursor);
            if ($next->lessThanOrEqualTo($cursor)) {
                break;
            }
            $periodEnd = $next->lessThan($contractEnd) ? $next : $contractEnd;
            if ($periodEnd->greaterThan($closedThrough)) {
                break;
            }
            $periods->push($this->period(
                $agreement,
                $cursor,
                $periodEnd,
                $sequence++,
                $periodEnd->greaterThanOrEqualTo($contractEnd),
            ));
            $cursor = $periodEnd;
        }

        return $periods;
    }

    /**
     * @param  array<string, mixed>  $period
     */
    public function persist(RentalAgreement $agreement, array $period, ?int $closedBy = null): RentalBillingPeriod
    {
        $side = RentalFinancialSide::fromDirection($agreement->direction);
        $fingerprint = $this->fingerprint($agreement, $period);

        return RentalBillingPeriod::query()->firstOrCreate([
            'tenant_id' => $agreement->tenant_id,
            'fingerprint' => $fingerprint,
        ], [
            'tenant_id' => $agreement->tenant_id,
            'organization_unit_id' => $agreement->organization_unit_id,
            'agreement_id' => $agreement->getKey(),
            'rate_snapshot_id' => $agreement->rateSnapshot?->getKey(),
            'agreement_direction' => $agreement->direction->value,
            'financial_side' => $side->value,
            'party_type' => $agreement->party_type->value,
            'party_id' => $agreement->party_id,
            'period_start' => $period['start'],
            'period_end' => $period['end'],
            'billing_cycle_key' => $period['key'],
            'period_sequence' => $period['sequence'],
            'billing_cycle' => $agreement->billing_cycle->value,
            'billing_basis' => $agreement->billing_basis->value,
            'proration_rule' => (string) $agreement->proration_rule,
            'status' => 'closed',
            'is_final' => (bool) $period['is_final'],
            'closed_at' => now(),
            'closed_by' => $closedBy,
            'fingerprint' => $fingerprint,
        ]);
    }

    /**
     * @param  array<string, mixed>  $period
     */
    public function fingerprint(RentalAgreement $agreement, array $period): string
    {
        return hash('sha256', implode('|', [
            (string) $agreement->tenant_id,
            (string) $agreement->getKey(),
            RentalFinancialSide::fromDirection($agreement->direction)->value,
            (string) $agreement->rateSnapshot?->getKey(),
            CarbonImmutable::parse($period['start'])->toDateTimeString(),
            CarbonImmutable::parse($period['end'])->toDateTimeString(),
            (string) $period['key'],
        ]));
    }

    private function closedThrough(
        RentalAgreement $agreement,
        CarbonImmutable $contractEnd,
        CarbonImmutable $asOf,
    ): CarbonImmutable {
        if (in_array($agreement->status, [
            RentalAgreementStatus::Returned,
            RentalAgreementStatus::Completed,
        ], true)) {
            return $contractEnd;
        }

        return $contractEnd->lessThan($asOf) ? $contractEnd : $asOf;
    }

    private function finalEligible(
        RentalAgreement $agreement,
        CarbonImmutable $contractEnd,
        CarbonImmutable $asOf,
    ): bool {
        return in_array($agreement->status, [
            RentalAgreementStatus::Returned,
            RentalAgreementStatus::Completed,
        ], true) || $contractEnd->lessThanOrEqualTo($asOf);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function perTripPeriods(RentalAgreement $agreement, CarbonImmutable $asOf): Collection
    {
        $contexts = $agreement->usageContexts()
            ->where('tenant_id', $agreement->tenant_id)
            ->where('organization_unit_id', $agreement->organization_unit_id)
            ->whereHas('usageLog', fn ($query) => $query->where('status', RentalUsageLogStatus::Approved->value))
            ->with('usageLog')
            ->orderBy('usage_log_id')
            ->get();
        $sequence = 1;

        return $contexts
            ->map(function ($context) use ($agreement, $asOf, &$sequence): ?array {
                $log = $context->usageLog;
                if (! $log instanceof RentalUsageLog || $log->effective_at === null) {
                    return null;
                }
                $start = CarbonImmutable::parse($log->effective_at);
                $minutes = max(1, (int) $log->working_minutes);
                $end = $start->addMinutes($minutes);
                if (! $this->finalEligible($agreement, $end, $asOf) && $end->greaterThan($asOf)) {
                    return null;
                }

                return $this->period($agreement, $start, $end, $sequence++, false, 'trip-'.$log->getKey());
            })
            ->filter()
            ->values();
    }

    private function nextBoundary(RentalAgreement $agreement, CarbonImmutable $cursor): CarbonImmutable
    {
        return match ($agreement->billing_cycle) {
            RentalBillingCycle::Hourly => $cursor->addHour(),
            RentalBillingCycle::Daily => $cursor->addDay(),
            RentalBillingCycle::Weekly => $cursor->addWeek(),
            RentalBillingCycle::Monthly => $this->monthlyBoundary($agreement, $cursor),
            RentalBillingCycle::Anniversary => $this->anniversaryBoundary($agreement, $cursor),
            RentalBillingCycle::FixedPeriod => $cursor->addDays($agreement->billing_period_days ?? 30),
            default => $cursor->addDay(),
        };
    }

    private function monthlyBoundary(RentalAgreement $agreement, CarbonImmutable $cursor): CarbonImmutable
    {
        return match ($agreement->billing_basis) {
            RentalBillingBasis::AnniversaryMonth => $this->anniversaryBoundary($agreement, $cursor),
            RentalBillingBasis::FixedThirtyDay => $cursor->addDays(30),
            RentalBillingBasis::CalendarMonth => $cursor->addMonthNoOverflow()->firstOfMonth()->startOfDay(),
            default => $this->anniversaryBoundary($agreement, $cursor),
        };
    }

    private function anniversaryBoundary(RentalAgreement $agreement, CarbonImmutable $cursor): CarbonImmutable
    {
        $anchor = CarbonImmutable::parse($agreement->start_at);
        $anchorDay = (int) $anchor->day;
        $anchorIsEndOfMonth = $anchorDay === (int) $anchor->daysInMonth;
        $next = $cursor->addMonthNoOverflow();
        $day = $anchorIsEndOfMonth ? (int) $next->daysInMonth : min($anchorDay, (int) $next->daysInMonth);

        return $next->setDay($day);
    }

    /**
     * @return array<string, mixed>
     */
    private function period(
        RentalAgreement $agreement,
        CarbonImmutable $start,
        CarbonImmutable $end,
        int $sequence,
        bool $isFinal,
        ?string $key = null,
    ): array {
        return [
            'start' => $start,
            'end' => $end,
            'key' => $key ?? $agreement->billing_cycle->value.'-'.$sequence,
            'sequence' => $sequence,
            'is_final' => $isFinal,
        ];
    }
}
