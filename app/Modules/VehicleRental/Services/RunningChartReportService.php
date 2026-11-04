<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Modules\Core\Services\DecimalMath;
use Modules\VehicleRental\Constants\OperationalFields;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\DriverMode;
use Modules\VehicleRental\Enums\RunningChartStatus;
use Modules\VehicleRental\Models\CustomerAgreement;
use Modules\VehicleRental\Models\RunningChart;
use Modules\VehicleRental\Models\VehicleUse;

final class RunningChartReportService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly RentalAuthorization $authorization,
    ) {}

    /**
     * @param  array{agreement_id: int, start_date: string, end_date: string, vehicle_use_id?: ?int}  $params
     * @return array<string, mixed>
     */
    public function generate(AgreementContext $context, array $params): array
    {
        $this->authorization->assertChart($context, \Modules\VehicleRental\Enums\RunningChartAction::Create, false);

        if (empty($params['agreement_id'])) {
            throw ValidationException::withMessages(['agreement_id' => ['Specify the customer agreement to generate the running chart sheet.']]);
        }
        if (empty($params['start_date']) || empty($params['end_date'])) {
            throw ValidationException::withMessages(['start_date' => ['Specify the start date and end date for the running chart sheet.']]);
        }

        $startDate = (string) $params['start_date'];
        $endDate = (string) $params['end_date'];

        $start = CarbonImmutable::parse($startDate)->startOfDay();
        $end = CarbonImmutable::parse($endDate)->endOfDay();

        if ($end->lessThan($start)) {
            throw ValidationException::withMessages(['end_date' => ['End date must be on or after start date.']]);
        }

        /** @var CustomerAgreement $agreement */
        $agreement = CustomerAgreement::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->with([
                'customer',
                'tenant',
                'vehicleUses.vehicle',
                'vehicleUses.replacesUse.vehicle',
            ])
            ->findOrFail((int) $params['agreement_id']);

        $selectedUseId = ! empty($params['vehicle_use_id']) ? (int) $params['vehicle_use_id'] : null;

        $useIds = $selectedUseId !== null
            ? [$selectedUseId]
            : $agreement->vehicleUses->pluck('id')->all();

        // Primary agreement vehicle number
        $primaryUse = $agreement->vehicleUses->firstWhere('replaces_use_id', null) ?? $agreement->vehicleUses->first();
        $agreementVehicleNo = $primaryUse?->vehicle_label_snapshot
            ?? $primaryUse?->vehicle?->registration_number
            ?? $primaryUse?->vehicle?->vehicle_number
            ?? 'N/A';

        // Load all running chart logs in period
        $charts = RunningChart::query()
            ->forTenant($context->tenantId)
            ->whereIn('vehicle_use_id', $useIds)
            ->where('starts_at', '>=', $start->format(OperationalFields::DATABASE_TIMESTAMP_FORMAT))
            ->where('starts_at', '<=', $end->format(OperationalFields::DATABASE_TIMESTAMP_FORMAT))
            ->whereIn('status', [RunningChartStatus::Draft->value, RunningChartStatus::Finalized->value])
            ->with(['vehicleUse.vehicle'])
            ->orderBy('starts_at')
            ->get();

        $chartsByDate = $charts->groupBy(fn (RunningChart $chart): string => CarbonImmutable::parse($chart->starts_at)->toDateString());

        $current = $start->copy();
        $endDay = CarbonImmutable::parse($endDate)->startOfDay();

        $rows = [];
        $cumulativeMileage = 0.0;
        $totalComparativeMileage = 0.0;
        $totalNormalOtMinutes = 0;
        $totalDoubleOtMinutes = 0;
        $totalTripleOtMinutes = 0;
        $totalNightOut = 0;
        $totalGarageKm = 0.0;

        while ($current->lessThanOrEqualTo($endDay)) {
            $dateStr = $current->toDateString();
            $dayCharts = $chartsByDate->get($dateStr);

            if ($dayCharts !== null && $dayCharts->isNotEmpty()) {
                $firstChart = $dayCharts->first();
                $lastChart = $dayCharts->last();

                $startOdo = $firstChart->start_odometer !== null ? (float) $firstChart->start_odometer : 0.0;
                $finishOdo = $lastChart->end_odometer !== null ? (float) $lastChart->end_odometer : 0.0;

                // Daily mileage from commercial_km or odometer delta
                $dayMileage = 0.0;
                foreach ($dayCharts as $c) {
                    if ($c->commercial_km !== null) {
                        $dayMileage += (float) $c->commercial_km;
                    } elseif ($c->start_odometer !== null && $c->end_odometer !== null) {
                        $dayMileage += max(0.0, (float) $c->end_odometer - (float) $c->start_odometer);
                    }
                }

                $cumulativeMileage += $dayMileage;
                $totalComparativeMileage += $dayMileage;

                $onTime = $firstChart->starts_at ? CarbonImmutable::parse($firstChart->starts_at)->format('h:i A') : '';
                $offTime = $lastChart->ends_at ? CarbonImmutable::parse($lastChart->ends_at)->format('h:i A') : '';

                if ($firstChart->starts_at && $lastChart->ends_at) {
                    $startDt = CarbonImmutable::parse($firstChart->starts_at);
                    $endDt = CarbonImmutable::parse($lastChart->ends_at);
                    if ($endDt->toDateString() !== $startDt->toDateString()) {
                        $offTime .= ' (+1)';
                    }
                }

                $normMin = (int) $dayCharts->sum('normal_ot_minutes');
                $doubleMin = (int) $dayCharts->sum('double_ot_minutes');
                $tripleMin = (int) $dayCharts->sum('triple_ot_minutes');
                $dayNight = (int) $dayCharts->sum('night_outs');
                $dayGarage = (float) $dayCharts->sum('garage_km');

                $totalNormalOtMinutes += $normMin;
                $totalDoubleOtMinutes += $doubleMin;
                $totalTripleOtMinutes += $tripleMin;
                $totalNightOut += $dayNight;
                $totalGarageKm += $dayGarage;

                $dayTotalOtHours = ($normMin + $doubleMin + $tripleMin) / 60.0;
                $otType = $current->isSunday() ? 'D' : ($doubleMin > 0 ? 'D' : ($normMin > 0 ? 'N' : ''));

                $remarks = $dayCharts->pluck('notes')->filter()->implode(', ');
                if (empty($remarks)) {
                    $remarks = $dayCharts->pluck('driver_observation')->filter()->implode(', ');
                }

                $driverName = $firstChart->driver_name_snapshot ?? '';

                $rows[] = [
                    'date' => $current->format('d/m/Y'),
                    'raw_date' => $dateStr,
                    'start_odometer' => $startOdo > 0 ? number_format($startOdo, 0, '.', '') : '0',
                    'finish_odometer' => $finishOdo > 0 ? number_format($finishOdo, 0, '.', '') : '0',
                    'comparative_mileage' => (int) round($dayMileage),
                    'cumulative_mileage' => (int) round($cumulativeMileage),
                    'on_time' => $onTime,
                    'off_time' => $offTime,
                    'ot_hours' => $dayTotalOtHours > 0 ? number_format($dayTotalOtHours, 2, '.', '') : '',
                    'ot_type' => $otType,
                    'night_out' => $dayNight > 0 ? (string) $dayNight : '',
                    'pass' => '',
                    'garage_km' => $dayGarage > 0 ? number_format($dayGarage, 1, '.', '') : '',
                    'remarks' => $remarks,
                    'driver_name' => $driverName,
                    'is_active_day' => true,
                ];
            } else {
                // Non-operational / idle day
                $rows[] = [
                    'date' => $current->format('d/m/Y'),
                    'raw_date' => $dateStr,
                    'start_odometer' => '0',
                    'finish_odometer' => '0',
                    'comparative_mileage' => 0,
                    'cumulative_mileage' => (int) round($cumulativeMileage),
                    'on_time' => '',
                    'off_time' => '12:00 AM',
                    'ot_hours' => '',
                    'ot_type' => $current->isSunday() ? 'D' : '',
                    'night_out' => '',
                    'pass' => '',
                    'garage_km' => '',
                    'remarks' => '',
                    'driver_name' => '',
                    'is_active_day' => false,
                ];
            }

            $current = $current->addDay();
        }

        $termsIncludedKm = $agreement->terms['included_km'] ?? null;
        $includedKm = $termsIncludedKm !== null ? (float) $termsIncludedKm : 0.0;
        $finalCumulative = (int) round($cumulativeMileage);
        $excessKm = ($includedKm > 0 && $finalCumulative > $includedKm)
            ? (int) round($finalCumulative - $includedKm)
            : 0;

        $rentalModeLabel = match ($agreement->driver_mode) {
            DriverMode::WithDriver => 'WITH DRIVER MONTHLY BASIS',
            DriverMode::SelfDrive => 'SELF DRIVE MONTHLY BASIS',
            default => 'MONTHLY BASIS VEHICLE',
        };

        $totalNormalOtHours = $totalNormalOtMinutes / 60.0;
        $totalDoubleOtHours = $totalDoubleOtMinutes / 60.0;
        $totalTripleOtHours = $totalTripleOtMinutes / 60.0;
        $totalOtHours = ($totalNormalOtMinutes + $totalDoubleOtMinutes + $totalTripleOtMinutes) / 60.0;

        return [
            'header' => [
                'company_name' => $agreement->tenant?->name ?? (config('app.name') ?: 'AutoERP'),
                'report_title' => 'RUNNING CHART FOR LESSEE - Monthly Basis Vehicles',
                'rental_mode' => $rentalModeLabel,
                'client_name' => $agreement->customer?->name ?? $agreement->party_name_snapshot ?? 'N/A',
                'duration' => $start->format('d/m/Y').' To '.$endDay->format('d/m/Y'),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'page' => 1,
                'report_date' => now()->format('d/m/Y'),
                'agreement_number' => $agreement->reference,
                'agreement_id' => $agreement->id,
                'agreement_vehicle_number' => $agreementVehicleNo,
            ],
            'rows' => $rows,
            'summary' => [
                'total_comparative_mileage' => (int) round($totalComparativeMileage),
                'final_cumulative_mileage' => $finalCumulative,
                'total_normal_ot_hours' => number_format($totalNormalOtHours, 2, '.', ''),
                'total_double_ot_hours' => number_format($totalDoubleOtHours, 2, '.', ''),
                'total_triple_ot_hours' => number_format($totalTripleOtHours, 2, '.', ''),
                'total_ot_hours' => number_format($totalOtHours, 2, '.', ''),
                'total_night_out' => $totalNightOut,
                'total_garage_km' => (int) round($totalGarageKm),
                'total_pass' => '0.00',
                'included_km' => (int) round($includedKm),
                'excess_km' => $excessKm,
            ],
            'footer' => [
                'software_provider' => 'Software Provided by : AutoERP Computer Systems',
            ],
        ];
    }
}
