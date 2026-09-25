<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Modules\Configuration\Contracts\ConfigurationResolverInterface;
use Modules\VehicleRental\Constants\AgreementFields;
use Modules\VehicleRental\Constants\RentalConfiguration;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\MileagePolicy;
use Modules\VehicleRental\Enums\RentalBasis;
use Modules\VehicleRental\Models\Agreement;
use Modules\VehicleRental\Models\CustomerUsageCharge;
use Modules\VehicleRental\Models\OwnerUsageCharge;
use Modules\VehicleRental\Models\RentalCharge;
use Modules\VehicleRental\Models\RunningChart;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class MileageAllowance
{
    public function __construct(private readonly ConfigurationResolverInterface $configuration) {}

    public function quote(AgreementKind $kind, AgreementContext $context, Agreement $agreement, RunningChart $chart, array $terms): array
    {
        $charges = $this->charges($kind, $context, $agreement->id);
        // Once any assessment exists (even voided), preserve this agreement's commercial timezone.
        $first = (clone $charges)->orderBy('id')->first();
        $timezone = $first?->calculation['timezone'] ?? $this->configuration->value(RentalConfiguration::WORKSPACE_TIMEZONE, $context->tenantId, $context->organizationUnitId);
        $start = $chart->starts_at->setTimezone($timezone);
        $end = $chart->ends_at->setTimezone($timezone);
        $anchor = CarbonImmutable::parse($agreement->starts_on->toDateString(), $timezone)->startOfDay();
        if ($agreement->basis === RentalBasis::Daily) {
            $cycleStart = $start->startOfDay();
            $cycleEnd = $cycleStart->addDay();
        } else {
            $offset = ($start->year - $anchor->year) * MileagePolicy::MONTHS_PER_YEAR + $start->month - $anchor->month;
            if ($anchor->addMonthsNoOverflow($offset)->greaterThan($start)) {
                $offset--;
            }
            $cycleStart = $anchor->addMonthsNoOverflow($offset);
            $cycleEnd = $anchor->addMonthsNoOverflow($offset + 1);
        }
        $coveredEnd = $agreement->ends_on === null ? $cycleEnd : $cycleEnd->min(CarbonImmutable::parse($agreement->ends_on->toDateString(), $timezone)->addDay()->startOfDay());
        if ($start->lessThan($anchor) || $end->greaterThan($coveredEnd)) {
            throw ValidationException::withMessages(['mileage' => ['Mileage evidence must fit wholly within one covered agreement cycle in '.$timezone.'. Do not estimate a distance split across cycles.']]);
        }
        $distance = $chart->commercial_km;
        $included = $terms['included_km'] ?? null;
        $rate = $terms['excess_km_rate'] ?? null;
        if ($distance === null || $included === null || $rate === null) {
            throw ValidationException::withMessages(['mileage' => ['Record commercial KM, included KM and excess-KM rate explicitly. Blank is not zero.']]);
        }
        $cycleDays = (int) $cycleStart->diffInDays($cycleEnd);
        $coveredDays = (int) $cycleStart->diffInDays($coveredEnd);
        $allowance = bcdiv(bcmul($included, (string) $coveredDays, AgreementFields::DECIMAL_SCALE), (string) $cycleDays, AgreementFields::DECIMAL_SCALE);
        $previousDistance = AgreementFields::ZERO;
        $previousAmount = AgreementFields::ZERO;
        $poolHead = MileagePolicy::EMPTY_POOL_REVISION;
        foreach ((clone $charges)->whereNull('voided_at')->where('period_from', $cycleStart->toDateString())->orderBy('id')->get() as $charge) {
            $previousDistance = bcadd($previousDistance, $charge->calculation['distance'], AgreementFields::DECIMAL_SCALE);
            $previousAmount = bcadd($previousAmount, $charge->amount, AgreementFields::DECIMAL_SCALE);
            $poolHead = $charge->id;
        }
        $totalDistance = bcadd($previousDistance, $distance, AgreementFields::DECIMAL_SCALE);
        $excess = $this->positive(bcsub($totalDistance, $allowance, AgreementFields::DECIMAL_SCALE));
        $amount = bcsub(bcmul($excess, $rate, AgreementFields::DECIMAL_SCALE), $previousAmount, AgreementFields::DECIMAL_SCALE);
        if (! preg_match(AgreementFields::DECIMAL_PATTERN, $amount)) {
            throw ValidationException::withMessages(['mileage' => ['The assessment does not fit supported monetary precision.']]);
        }
        $remainingBefore = $this->positive(bcsub($allowance, $previousDistance, AgreementFields::DECIMAL_SCALE));
        $used = bccomp($distance, $remainingBefore, AgreementFields::DECIMAL_SCALE) <= 0 ? $distance : $remainingBefore;

        return ['policy' => MileagePolicy::CommercialCalendarCycles->value, 'component' => MileagePolicy::COMPONENT,
            'timezone' => $timezone, 'supply_from' => $start->toDateString(), 'supply_until' => $end->subMicrosecond()->toDateString(), 'cycle_from' => $cycleStart->toDateString(), 'cycle_until' => $coveredEnd->subDay()->toDateString(),
            'cycle_days' => $cycleDays, 'covered_days' => $coveredDays, 'allowance' => $allowance, 'distance' => $distance,
            'included_applied' => $used, 'excess_km' => bcsub($distance, $used, AgreementFields::DECIMAL_SCALE),
            'pool_head' => $poolHead,
            'prior_distance' => $previousDistance, 'prior_amount' => $previousAmount,
            'rate' => $rate, 'amount' => $amount, 'currency' => $agreement->currency_code_snapshot];
    }

    public function assertReversible(AgreementKind $kind, AgreementContext $context, RentalCharge $charge): void
    {
        if ($charge->component === MileagePolicy::COMPONENT && $this->charges($kind, $context, (int) $charge->agreement_id)
            ->where('period_from', $charge->period_from)->whereNull('voided_at')->where('id', '>', $charge->id)->lockForUpdate()->first(['id']) !== null) {
            throw new ConflictHttpException('Void later mileage assessments in this agreement cycle first; they depend on this allowance allocation. Release their invoices before voiding.');
        }
    }

    private function charges(AgreementKind $kind, AgreementContext $context, int $agreementId): Builder
    {
        $class = $kind === AgreementKind::Customer ? CustomerUsageCharge::class : OwnerUsageCharge::class;

        return $class::query()->forContext($context->tenantId, $context->organizationUnitId)->where('agreement_id', $agreementId)->where('component', MileagePolicy::COMPONENT);
    }

    private function positive(string $amount): string
    {
        return bccomp($amount, AgreementFields::ZERO, AgreementFields::DECIMAL_SCALE) > 0 ? $amount : bcadd(AgreementFields::ZERO, AgreementFields::ZERO, AgreementFields::DECIMAL_SCALE);
    }
}
