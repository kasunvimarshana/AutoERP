<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Modules\Core\Services\DecimalMath;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\Constants\OperationalFields;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\AgreementStatus;
use Modules\VehicleRental\Enums\RunningChartStatus;
use Modules\VehicleRental\Enums\VehicleUseStatus;
use Modules\VehicleRental\Models\OwnerAgreement;
use Modules\VehicleRental\Models\RunningChart;
use Modules\VehicleRental\Models\VehicleUse;

final class FleetUtilizationReportService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly RentalAuthorization $authorization,
    ) {}

    /**
     * @param  array{start_date: string, end_date: string, ownership_type?: ?string, custody_status?: ?string, vehicle_id?: ?int}  $params
     * @return array<string, mixed>
     */
    public function generate(AgreementContext $context, array $params): array
    {
        $this->authorization->assertReport($context);

        if (empty($params['start_date']) || empty($params['end_date'])) {
            throw ValidationException::withMessages(['start_date' => ['Specify both start date and end date for the utilization report.']]);
        }

        $startDate = (string) $params['start_date'];
        $endDate = (string) $params['end_date'];

        $start = CarbonImmutable::parse($startDate)->startOfDay();
        $end = CarbonImmutable::parse($endDate)->endOfDay();

        if ($end->lessThan($start)) {
            throw ValidationException::withMessages(['end_date' => ['End date must be on or after start date.']]);
        }

        $periodDays = max(1, $start->diffInDays($end) + 1);
        $ownershipFilter = (string) ($params['ownership_type'] ?? 'all');
        $custodyFilter = (string) ($params['custody_status'] ?? 'all');
        $selectedVehicleId = ! empty($params['vehicle_id']) ? (int) $params['vehicle_id'] : null;

        // Query fleet vehicles
        $vehicles = Vehicle::query()
            ->where('tenant_id', $context->tenantId)
            ->when($selectedVehicleId !== null, fn (Builder $q) => $q->where('id', $selectedVehicleId))
            ->orderBy('vehicle_number')
            ->get();

        // Query owner agreements covering period
        $ownerAgreements = OwnerAgreement::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->with('party')
            ->where('starts_on', '<=', $endDate)
            ->where(fn (Builder $q) => $q->whereNull('ends_on')->orWhere('ends_on', '>=', $startDate))
            ->whereIn('status', [AgreementStatus::Active->value, AgreementStatus::Closed->value])
            ->get()
            ->groupBy('vehicle_id');

        // Query vehicle uses in period
        $vehicleUses = VehicleUse::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->with(['customerAgreement.customer', 'vehicle'])
            ->where('starts_at', '<=', $end->format(OperationalFields::DATABASE_TIMESTAMP_FORMAT))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $start->format(OperationalFields::DATABASE_TIMESTAMP_FORMAT)))
            ->orderBy('starts_at')
            ->get()
            ->groupBy('vehicle_id');

        // Query running charts in period
        $charts = RunningChart::query()
            ->forTenant($context->tenantId)
            ->where('starts_at', '>=', $start->format(OperationalFields::DATABASE_TIMESTAMP_FORMAT))
            ->where('starts_at', '<=', $end->format(OperationalFields::DATABASE_TIMESTAMP_FORMAT))
            ->whereIn('status', [RunningChartStatus::Draft->value, RunningChartStatus::Finalized->value])
            ->with('vehicleUse')
            ->get()
            ->groupBy(fn (RunningChart $c): int => (int) $c->vehicleUse?->vehicle_id);

        $rows = [];
        $totalDistance = 0.0;
        $totalInCustody = 0;
        $totalAvailable = 0;
        $totalCompanyOwned = 0;
        $totalOwnerLeased = 0;
        $utilizationSum = 0.0;

        foreach ($vehicles as $vehicle) {
            $vehicleOwnerAgreements = $ownerAgreements->get($vehicle->id) ?? collect();
            $activeOwnerAgreement = $vehicleOwnerAgreements->first();
            $isOwnerLeased = $activeOwnerAgreement !== null;

            if ($ownershipFilter === 'company' && $isOwnerLeased) {
                continue;
            }
            if ($ownershipFilter === 'owner_leased' && ! $isOwnerLeased) {
                continue;
            }

            $uses = $vehicleUses->get($vehicle->id) ?? collect();
            $inCustodyUse = $uses->firstWhere('status', VehicleUseStatus::InCustody);
            $plannedUse = $uses->firstWhere('status', VehicleUseStatus::Planned);

            if ($inCustodyUse !== null) {
                $statusKey = 'in_custody';
                $statusLabel = 'In Custody';
                $activeUse = $inCustodyUse;
            } elseif ($plannedUse !== null) {
                $statusKey = 'planned';
                $statusLabel = 'Planned';
                $activeUse = $plannedUse;
            } else {
                $statusKey = 'available';
                $statusLabel = 'Available / Idle';
                $activeUse = $uses->last();
            }

            if ($custodyFilter !== 'all' && $custodyFilter !== $statusKey) {
                continue;
            }

            // Calculate custody days within period
            $daysInCustody = 0;
            foreach ($uses as $use) {
                if (in_array($use->status, [VehicleUseStatus::InCustody, VehicleUseStatus::Returned], true)) {
                    $useStart = max($start, CarbonImmutable::parse($use->starts_at));
                    $rawEnd = $use->returned_at ?? $use->ends_at ?? now();
                    $useEnd = min($end, CarbonImmutable::parse($rawEnd));
                    if ($useStart->lessThanOrEqualTo($useEnd)) {
                        $daysInCustody += (int) ceil($useStart->floatDiffInDays($useEnd));
                    }
                }
            }
            $daysInCustody = min($periodDays, $daysInCustody);
            $utilizationRate = round(($daysInCustody / $periodDays) * 100, 1);

            // Compute distance from charts
            $vehicleCharts = $charts->get($vehicle->id) ?? collect();
            $vehicleDistance = 0.0;
            foreach ($vehicleCharts as $c) {
                if ($c->commercial_km !== null) {
                    $vehicleDistance += (float) $c->commercial_km;
                } elseif ($c->start_odometer !== null && $c->end_odometer !== null) {
                    $vehicleDistance += max(0.0, (float) $c->end_odometer - (float) $c->start_odometer);
                }
            }
            $lastOdometer = $vehicleCharts->last()?->end_odometer
                ?? $activeUse?->return_odometer
                ?? $activeUse?->handover_odometer
                ?? $vehicle->current_odometer
                ?? 0.0;

            $customerName = $activeUse?->customerAgreement?->customer?->name
                ?? $activeUse?->customerAgreement?->party_name_snapshot
                ?? '-';
            $agreementRef = $activeUse?->customerAgreement?->reference ?? '-';
            $driverName = $vehicleCharts->last()?->driver_name_snapshot ?? '-';

            if ($isOwnerLeased) {
                $totalOwnerLeased++;
            } else {
                $totalCompanyOwned++;
            }

            if ($statusKey === 'in_custody') {
                $totalInCustody++;
            } elseif ($statusKey === 'available') {
                $totalAvailable++;
            }

            $totalDistance += $vehicleDistance;
            $utilizationSum += $utilizationRate;

            $rows[] = [
                'vehicle_id' => $vehicle->id,
                'registration_number' => $vehicle->registration_number ?? $vehicle->vehicle_number,
                'vehicle_make_model' => trim(($vehicle->make ?? '').' '.($vehicle->model ?? '')) ?: 'N/A',
                'ownership_type' => $isOwnerLeased ? 'Owner Leased' : 'Company Owned',
                'owner_name' => $isOwnerLeased ? ($activeOwnerAgreement->party?->name ?? 'N/A') : 'Internal Fleet',
                'owner_agreement_number' => $activeOwnerAgreement?->reference ?? '-',
                'custody_status' => $statusLabel,
                'status_key' => $statusKey,
                'current_customer' => $customerName,
                'current_agreement' => $agreementRef,
                'current_driver' => $driverName,
                'current_odometer' => number_format((float) $lastOdometer, 0, '.', ''),
                'period_distance_km' => (int) round($vehicleDistance),
                'days_in_custody' => $daysInCustody,
                'utilization_rate' => $utilizationRate,
            ];
        }

        $totalEvaluated = count($rows);
        $averageUtilization = $totalEvaluated > 0 ? round($utilizationSum / $totalEvaluated, 1) : 0.0;

        return [
            'header' => [
                'company_name' => config('app.name') ?: 'AutoERP',
                'report_title' => 'FLEET UTILIZATION & STATUS REPORT',
                'duration' => $start->format('d/m/Y').' To '.$end->format('d/m/Y'),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'report_date' => now()->format('d/m/Y'),
            ],
            'filters' => [
                'ownership_type' => $ownershipFilter,
                'custody_status' => $custodyFilter,
            ],
            'summary' => [
                'total_vehicles' => $totalEvaluated,
                'company_owned_count' => $totalCompanyOwned,
                'owner_leased_count' => $totalOwnerLeased,
                'in_custody_count' => $totalInCustody,
                'available_count' => $totalAvailable,
                'average_utilization_rate' => $averageUtilization,
                'total_distance_km' => (int) round($totalDistance),
            ],
            'rows' => $rows,
            'footer' => [
                'software_provider' => 'Software Provided by : AutoERP Computer Systems',
            ],
        ];
    }
}
