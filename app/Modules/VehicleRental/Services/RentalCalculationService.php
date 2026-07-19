<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\VehicleRental\DTOs\RentalCalculationPeriodData;
use Modules\VehicleRental\DTOs\RentalCalculationLineResult;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Enums\RentalCalculationSide;
use Modules\VehicleRental\Enums\RentalCalculationStatus;
use Modules\VehicleRental\Enums\RentalRateVersionStatus;
use Modules\VehicleRental\Enums\RentalRunningChartStatus;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalCalculation;
use Modules\VehicleRental\Models\RentalCalculationLine;
use Modules\VehicleRental\Models\RentalCalculationSource;
use Modules\VehicleRental\Models\RentalRateVersion;
use Modules\VehicleRental\Models\RentalRunningChart;

final class RentalCalculationService
{
    public const RELATIONS = [
        'agreement.customer',
        'agreement.supplier',
        'currency',
        'rateVersion.lines',
        'lines',
        'sources.runningChart.assignment.vehicle.model',
    ];

    public function __construct(
        private readonly RentalCalculationEngine $engine,
        private readonly RentalNumberService $numbers,
    ) {}

    public function calculate(RentalAgreement $agreement, RentalCalculationPeriodData $data): RentalCalculation
    {
        return DB::transaction(function () use ($agreement, $data): RentalCalculation {
            $agreement = RentalAgreement::query()
                ->forContext($data->tenantId, $data->organizationUnitId)
                ->lockForUpdate()
                ->findOrFail($agreement->getKey());
            if (! in_array($agreement->status, [RentalAgreementStatus::Active, RentalAgreementStatus::Closed], true)) {
                throw new InvalidArgumentException('Only active or closed agreements can produce rental calculations.');
            }

            $periodStart = CarbonImmutable::parse($data->periodStart)->startOfDay();
            $periodEnd = CarbonImmutable::parse($data->periodEnd)->startOfDay();
            if ($periodEnd->lessThan($periodStart)) {
                throw new InvalidArgumentException('Calculation period end cannot be before its start.');
            }
            if ($periodStart->lessThan(CarbonImmutable::parse($agreement->starts_on))) {
                throw new InvalidArgumentException('Calculation period starts before the agreement.');
            }
            if ($agreement->ends_on !== null && $periodEnd->greaterThan(CarbonImmutable::parse($agreement->ends_on))) {
                throw new InvalidArgumentException('Calculation period ends after the agreement.');
            }

            $side = RentalCalculationSide::fromAgreementKind($agreement->kind);
            if (RentalCalculation::query()
                ->forContext($data->tenantId, $data->organizationUnitId)
                ->where('agreement_id', $agreement->getKey())
                ->where('side', $side->value)
                ->where('active_marker', true)
                ->where('period_start', '<=', $periodEnd->toDateString())
                ->where('period_end', '>=', $periodStart->toDateString())
                ->lockForUpdate()
                ->exists()) {
                throw new InvalidArgumentException('An active calculation already overlaps this agreement period.');
            }

            $rateVersion = $this->rateVersion($agreement, $periodStart, $periodEnd);
            $charts = $this->charts($agreement, $side, $periodStart, $periodEnd);
            if ($charts->contains(fn (RentalRunningChart $chart): bool => $chart->calculationSources
                ->contains(fn (RentalCalculationSource $source): bool => $source->side === $side && $source->active_marker === true))) {
                throw new InvalidArgumentException('One or more running charts are already consumed by an active calculation on this side.');
            }

            $result = $this->engine->calculate($agreement, $rateVersion, $periodStart, $periodEnd, $charts);
            $calculation = new RentalCalculation();
            $calculation->forceFill([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'calculation_number' => $this->numbers->calculation($data->tenantId, $data->organizationUnitId),
                'agreement_id' => $agreement->getKey(),
                'rate_version_id' => $rateVersion->getKey(),
                'currency_id' => $agreement->currency_id,
                'side' => $side->value,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'chart_count' => $charts->count(),
                'operating_days' => $result->operatingDays,
                'commercial_km' => $result->commercialKm,
                'included_km' => $result->includedKm,
                'excess_km' => $result->excessKm,
                'subtotal_amount' => $result->subtotalAmount,
                'status' => RentalCalculationStatus::Calculated->value,
                'active_marker' => true,
                'created_by' => $data->actorId,
            ])->save();

            foreach ($result->lines as $index => $line) {
                $this->storeLine($calculation, $line, $index + 1);
            }
            foreach ($charts as $chart) {
                $source = new RentalCalculationSource();
                $source->forceFill([
                    'tenant_id' => $data->tenantId,
                    'organization_unit_id' => $data->organizationUnitId,
                    'calculation_id' => $calculation->getKey(),
                    'running_chart_id' => $chart->getKey(),
                    'side' => $side->value,
                    'active_marker' => true,
                ])->save();
            }

            return $this->load($calculation);
        });
    }

    public function cancel(RentalCalculation $calculation, int $expectedVersion, string $reason, ?int $actorId): RentalCalculation
    {
        return DB::transaction(function () use ($calculation, $expectedVersion, $reason, $actorId): RentalCalculation {
            $calculation = RentalCalculation::query()
                ->forContext((int) $calculation->tenant_id, $calculation->organization_unit_id === null ? null : (int) $calculation->organization_unit_id)
                ->lockForUpdate()
                ->findOrFail($calculation->getKey());
            if ((int) $calculation->row_version !== $expectedVersion) {
                throw new InvalidArgumentException('Rental calculation was changed by another request. Reload it before continuing.');
            }
            if ($calculation->status !== RentalCalculationStatus::Calculated) {
                throw new InvalidArgumentException('Only active calculated records can be cancelled.');
            }
            if (mb_strlen(trim($reason)) < 5) {
                throw new InvalidArgumentException('Rental calculation cancellation reason must contain at least 5 characters.');
            }

            $sources = $calculation->sources()
                ->where('active_marker', true)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            foreach ($sources as $source) {
                $source->forceFill(['active_marker' => null])->save();
            }
            $calculation->forceFill([
                'status' => RentalCalculationStatus::Cancelled->value,
                'active_marker' => null,
                'cancelled_by' => $actorId,
                'cancelled_at' => now(),
                'cancellation_reason' => trim($reason),
                'row_version' => $expectedVersion + 1,
            ])->save();

            return $this->load($calculation);
        });
    }

    /** @return list<string> */
    public function relations(): array
    {
        return self::RELATIONS;
    }

    private function rateVersion(RentalAgreement $agreement, CarbonImmutable $periodStart, CarbonImmutable $periodEnd): RentalRateVersion
    {
        $versions = $agreement->rateVersions()
            ->where('status', RentalRateVersionStatus::Active->value)
            ->whereDate('effective_from', '<=', $periodStart->toDateString())
            ->where(function (Builder $query) use ($periodEnd): void {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $periodEnd->toDateString());
            })
            ->with('lines')
            ->lockForUpdate()
            ->get();
        if ($versions->count() !== 1) {
            throw new InvalidArgumentException('Calculation period must be covered by exactly one rental rate version. Split the period at rate revision boundaries.');
        }

        return $versions->firstOrFail();
    }

    /** @return Collection<int, RentalRunningChart> */
    private function charts(
        RentalAgreement $agreement,
        RentalCalculationSide $side,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
    ): Collection {
        $query = RentalRunningChart::query()
            ->forContext((int) $agreement->tenant_id, $agreement->organization_unit_id === null ? null : (int) $agreement->organization_unit_id)
            ->where('status', RentalRunningChartStatus::Finalized->value)
            ->where('active_marker', true)
            ->whereBetween('operational_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->with(['assignment', 'calculationSources'])
            ->orderBy('id');

        if ($side === RentalCalculationSide::Customer) {
            $query->whereHas('assignment', fn (Builder $assignment): Builder => $assignment->where('agreement_id', $agreement->getKey()));
        } else {
            $query->whereHas('assignment.sourceAssignment', fn (Builder $source): Builder => $source->where('agreement_id', $agreement->getKey()));
        }

        return $query->lockForUpdate()->get();
    }

    private function storeLine(RentalCalculation $calculation, RentalCalculationLineResult $result, int $lineNumber): void
    {
        $line = new RentalCalculationLine();
        $line->forceFill([
            'tenant_id' => $calculation->tenant_id,
            'organization_unit_id' => $calculation->organization_unit_id,
            'calculation_id' => $calculation->getKey(),
            'rate_line_id' => $result->rateLineId,
            'line_number' => $lineNumber,
            'rate_code' => $result->rateCode->value,
            'unit' => $result->unit->value,
            'quantity' => $result->quantity,
            'unit_rate' => $result->unitRate,
            'line_total' => $result->lineTotal,
            'is_taxable' => $result->isTaxable,
            'description' => $result->description,
        ])->save();
    }

    private function load(RentalCalculation $calculation): RentalCalculation
    {
        return $calculation->refresh()->load(self::RELATIONS);
    }
}
