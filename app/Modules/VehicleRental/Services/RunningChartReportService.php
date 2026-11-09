<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Modules\Core\Services\DecimalMath;
use Modules\VehicleRental\Constants\OperationalFields;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\AgreementStatus;
use Modules\VehicleRental\Enums\DriverMode;
use Modules\VehicleRental\Enums\RentalExpenseStatus;
use Modules\VehicleRental\Enums\RentalExpenseType;
use Modules\VehicleRental\Enums\RunningChartStatus;
use Modules\VehicleRental\Models\CustomerAgreement;
use Modules\VehicleRental\Models\OwnerAgreement;
use Modules\VehicleRental\Models\RentalExpense;
use Modules\VehicleRental\Models\RunningChart;
use Modules\VehicleRental\Models\VehicleUse;

final class RunningChartReportService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly RentalAuthorization $authorization,
    ) {}

    /**
     * @param  array{agreement_id: int, start_date: string, end_date: string, vehicle_use_id?: ?int, sheet_mode?: ?string}  $params
     * @return array<string, mixed>
     */
    public function generate(AgreementContext $context, array $params): array
    {
        $this->authorization->assertChart($context, \Modules\VehicleRental\Enums\RunningChartAction::Create, false);

        if (empty($params['agreement_id'])) {
            throw ValidationException::withMessages(['agreement_id' => ['Specify the rental agreement to generate the running chart sheet.']]);
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

        $agreementId = (int) $params['agreement_id'];
        $sheetMode = (string) ($params['sheet_mode'] ?? 'lessee');

        /** @var CustomerAgreement|null $customerAgreement */
        $customerAgreement = CustomerAgreement::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->with([
                'customer',
                'tenant',
                'vehicleUses.vehicle',
                'vehicleUses.ownerAgreement.party',
                'vehicleUses.replacesUse.vehicle',
            ])
            ->find($agreementId);

        /** @var OwnerAgreement|null $ownerAgreement */
        $ownerAgreement = null;

        if ($customerAgreement === null) {
            $ownerAgreement = OwnerAgreement::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->with([
                    'party',
                    'tenant',
                    'vehicle',
                ])
                ->find($agreementId);

            if ($ownerAgreement === null) {
                throw ValidationException::withMessages(['agreement_id' => ['Specified rental agreement was not found.']]);
            }
            $sheetMode = 'owner';
        }

        $selectedUseId = ! empty($params['vehicle_use_id']) ? (int) $params['vehicle_use_id'] : null;

        if ($customerAgreement !== null) {
            $useIds = $selectedUseId !== null
                ? [$selectedUseId]
                : $customerAgreement->vehicleUses->pluck('id')->all();

            $primaryUse = $customerAgreement->vehicleUses->firstWhere('replaces_use_id', null) ?? $customerAgreement->vehicleUses->first();

            if ($sheetMode === 'owner') {
                $ownerAgreement = $primaryUse?->ownerAgreement;
                if ($ownerAgreement === null && $primaryUse?->vehicle_id !== null) {
                    $ownerAgreement = OwnerAgreement::query()
                        ->forContext($context->tenantId, $context->organizationUnitId)
                        ->with(['party', 'tenant', 'vehicle'])
                        ->where('vehicle_id', $primaryUse->vehicle_id)
                        ->where('status', AgreementStatus::Active)
                        ->first();
                }
            }
        } else {
            // Generated directly from an Owner Agreement
            $ownerUses = VehicleUse::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->where(fn (Builder $q) => $q->where('owner_agreement_id', $ownerAgreement->id)->orWhere('vehicle_id', $ownerAgreement->vehicle_id))
                ->with(['customerAgreement.customer', 'vehicle'])
                ->get();

            $useIds = $selectedUseId !== null
                ? [$selectedUseId]
                : $ownerUses->pluck('id')->all();

            $primaryUse = $ownerUses->first();
        }

        $isOwnerSheet = $sheetMode === 'owner';

        // Vehicle registration label
        $agreementVehicleNo = $primaryUse?->vehicle_label_snapshot
            ?? $primaryUse?->vehicle?->registration_number
            ?? $primaryUse?->vehicle?->vehicle_number
            ?? $ownerAgreement?->vehicle?->registration_number
            ?? $ownerAgreement?->vehicle?->vehicle_number
            ?? 'N/A';

        // Header metadata
        $companyName = $customerAgreement?->tenant?->name ?? $ownerAgreement?->tenant?->name ?? (config('app.name') ?: 'AutoERP');
        $reportTitle = $isOwnerSheet
            ? "RUNNING CHART FOR VEHICLE OWNER - Monthly Basis Vehicles - With Lessee's Detail"
            : 'RUNNING CHART FOR LESSEE - Monthly Basis Vehicles';

        $lessorParty = $ownerAgreement?->party;
        $lessorId = $lessorParty !== null
            ? ($lessorParty->code ? ($lessorParty->code.' - '.$lessorParty->name) : $lessorParty->name)
            : 'N/A';
        $ownerName = $lessorParty?->name ?? 'N/A';

        $agreementNumber = $isOwnerSheet && $ownerAgreement !== null
            ? $ownerAgreement->reference
            : ($customerAgreement?->reference ?? $ownerAgreement?->reference ?? 'N/A');

        $clientName = $customerAgreement?->customer?->name
            ?? $customerAgreement?->party_name_snapshot
            ?? 'VARIOUS LESSEES';

        // Load all running chart logs in period
        $charts = RunningChart::query()
            ->forTenant($context->tenantId)
            ->whereIn('vehicle_use_id', $useIds)
            ->where('starts_at', '>=', $start->format(OperationalFields::DATABASE_TIMESTAMP_FORMAT))
            ->where('starts_at', '<=', $end->format(OperationalFields::DATABASE_TIMESTAMP_FORMAT))
            ->whereIn('status', [RunningChartStatus::Draft->value, RunningChartStatus::Finalized->value])
            ->with(['vehicleUse.vehicle', 'vehicleUse.customerAgreement.customer'])
            ->orderBy('starts_at')
            ->get();

        $chartsByDate = $charts->groupBy(fn (RunningChart $chart): string => CarbonImmutable::parse($chart->starts_at)->toDateString());

        // Toll / Pass outlays
        $chartIds = $charts->pluck('id')->all();
        $tollExpensesByChart = empty($chartIds) ? collect() : RentalExpense::query()
            ->forTenant($context->tenantId)
            ->whereIn('running_chart_id', $chartIds)
            ->where('expense_type', RentalExpenseType::Toll)
            ->whereNotIn('status', [RentalExpenseStatus::Rejected, RentalExpenseStatus::Reversed])
            ->get()
            ->groupBy('running_chart_id');

        $tollExpensesByDate = empty($useIds) ? collect() : RentalExpense::query()
            ->forTenant($context->tenantId)
            ->whereIn('vehicle_use_id', $useIds)
            ->whereNull('running_chart_id')
            ->where('expense_type', RentalExpenseType::Toll)
            ->where('expense_date', '>=', $start->toDateString())
            ->where('expense_date', '<=', $end->toDateString())
            ->whereNotIn('status', [RentalExpenseStatus::Rejected, RentalExpenseStatus::Reversed])
            ->get()
            ->groupBy(fn (RentalExpense $e): string => CarbonImmutable::parse($e->expense_date)->toDateString());

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
        $totalPass = 0.0;

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

                // Daily Toll / Pass outlay
                $dayToll = 0.0;
                foreach ($dayCharts as $c) {
                    $chartExpenses = $tollExpensesByChart->get($c->id);
                    if ($chartExpenses !== null) {
                        $dayToll += (float) $chartExpenses->sum('gross_amount');
                    }
                }
                $dateExpenses = $tollExpensesByDate->get($dateStr);
                if ($dateExpenses !== null) {
                    $dayToll += (float) $dateExpenses->sum('gross_amount');
                }
                $totalPass += $dayToll;

                $dayTotalOtHours = ($normMin + $doubleMin + $tripleMin) / 60.0;
                $otType = $current->isSunday() ? 'D' : ($doubleMin > 0 ? 'D' : ($normMin > 0 ? 'N' : ''));

                $remarks = $dayCharts->pluck('notes')->filter()->implode(', ');
                if (empty($remarks)) {
                    $remarks = $dayCharts->pluck('driver_observation')->filter()->implode(', ');
                }

                $driverName = $dayCharts->pluck('driver_name_snapshot')->filter()->unique()->implode(', ');

                $dayLessee = $firstChart->vehicleUse?->customerAgreement?->customer?->name
                    ?? $firstChart->vehicleUse?->customerAgreement?->party_name_snapshot
                    ?? ($customerAgreement?->customer?->name ?? $customerAgreement?->party_name_snapshot ?? '');

                $rows[] = [
                    'date' => $current->format('d/m/Y'),
                    'raw_date' => $dateStr,
                    'start_odometer' => $startOdo > 0 ? number_format($startOdo, 0, '.', '') : '0',
                    'finish_odometer' => $finishOdo > 0 ? number_format($finishOdo, 0, '.', '') : '0',
                    'comparative_mileage' => (int) round($dayMileage),
                    'cumulative_mileage' => (int) round($cumulativeMileage),
                    'on_time' => $onTime,
                    'off_time' => $offTime,
                    'lessee' => $dayLessee,
                    'ot_hours' => $dayTotalOtHours > 0 ? number_format($dayTotalOtHours, 2, '.', '') : '',
                    'ot_type' => $otType,
                    'night_out' => $dayNight > 0 ? (string) $dayNight : '',
                    'pass' => $dayToll > 0 ? number_format($dayToll, 2, '.', '') : '',
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
                    'lessee' => '',
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

        $activeAgreement = $customerAgreement ?? $ownerAgreement;
        $termsIncludedKm = $activeAgreement?->terms['included_km'] ?? null;
        $includedKm = $termsIncludedKm !== null ? (float) $termsIncludedKm : 0.0;
        $finalCumulative = (int) round($cumulativeMileage);
        $excessKm = ($includedKm > 0 && $finalCumulative > $includedKm)
            ? (int) round($finalCumulative - $includedKm)
            : 0;

        $driverMode = $customerAgreement?->driver_mode ?? $ownerAgreement?->driver_mode ?? DriverMode::WithDriver;
        $rentalModeLabel = match ($driverMode) {
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
                'company_name' => $companyName,
                'report_title' => $reportTitle,
                'is_owner_sheet' => $isOwnerSheet,
                'rental_mode' => $rentalModeLabel,
                'client_name' => $clientName,
                'lessor_id' => $lessorId,
                'owner_name' => $ownerName,
                'duration' => $start->format('d/m/Y').' To '.$endDay->format('d/m/Y'),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'page' => 1,
                'report_date' => now()->format('d/m/Y'),
                'agreement_number' => $agreementNumber,
                'agreement_id' => $agreementId,
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
                'total_pass' => number_format($totalPass, 2, '.', ''),
                'included_km' => (int) round($includedKm),
                'excess_km' => $excessKm,
            ],
            'footer' => [
                'software_provider' => 'Software Provided by : AutoERP Computer Systems',
            ],
        ];
    }
}
