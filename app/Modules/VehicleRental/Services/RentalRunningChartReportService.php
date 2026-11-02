<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\Core\Services\DecimalMath;
use Modules\VehicleRental\Enums\RentalAgreementKind;
use Modules\VehicleRental\Enums\RentalMode;
use Modules\VehicleRental\Enums\RentalUsageEventType;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalUsageLog;
use Modules\VehicleRental\Models\RentalVehicleAllocation;

final class RentalRunningChartReportService
{
    public function __construct(private readonly DecimalMath $math) {}

    /**
     * @param  array{agreement_id?: ?int, allocation_id?: ?int, start_date: string, end_date: string}  $params
     * @return array<string, mixed>
     */
    public function generate(int $tenantId, ?int $organizationUnitId, array $params): array
    {
        $startDate = (string) $params['start_date'];
        $endDate = (string) $params['end_date'];

        $agreement = $this->resolveAgreement($tenantId, $organizationUnitId, $params);
        $allocationIds = ! empty($params['allocation_id'])
            ? [(int) $params['allocation_id']]
            : $agreement->allocations->pluck('id')->all();

        // Primary agreement vehicle number
        $primaryAllocation = $agreement->allocations->firstWhere('replaces_allocation_id', null) ?? $agreement->allocations->first();
        $agreementVehicleNo = $primaryAllocation?->vehicle?->registration_number
            ?? $primaryAllocation?->vehicle?->vehicle_number
            ?? 'N/A';

        // Load all running chart logs in period
        /** @var Collection<int, RentalUsageLog> $logs */
        $logs = RentalUsageLog::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('vehicle_allocation_id', $allocationIds)
            ->whereDate('usage_date', '>=', $startDate)
            ->whereDate('usage_date', '<=', $endDate)
            ->with(['vehicle', 'driver', 'events', 'allocation.replacesAllocation.vehicle'])
            ->orderBy('usage_date')
            ->orderBy('operational_sequence')
            ->get();

        $logsByDate = $logs->groupBy(fn (RentalUsageLog $log): string => $log->usage_date->toDateString());

        $current = CarbonImmutable::parse($startDate);
        $end = CarbonImmutable::parse($endDate);

        $rows = [];
        $cumulativeMileage = 0.0;
        $totalComparativeMileage = 0.0;
        $totalNormalOtHours = 0.0;
        $totalDoubleOtHours = 0.0;
        $totalNightOut = 0.0;
        $totalGarageKm = 0.0;
        $totalPass = 0.0;
        $totalDriverOtAmount = 0.0;
        $totalDriverSalaryAmount = 0.0;

        while ($current->lessThanOrEqualTo($end)) {
            $dateStr = $current->toDateString();
            $dayLogs = $logsByDate->get($dateStr);

            if ($dayLogs !== null && $dayLogs->isNotEmpty()) {
                $firstLog = $dayLogs->first();
                $lastLog = $dayLogs->last();

                $startOdo = (float) $firstLog->start_odometer;
                $finishOdo = (float) $lastLog->end_odometer;
                $compMileage = (float) $dayLogs->sum('distance_km');
                $cumulativeMileage += $compMileage;
                $totalComparativeMileage += $compMileage;

                $driverIn = $firstLog->metadata['driver_in_time'] ?? null;
                $driverOut = $lastLog->metadata['driver_out_time'] ?? null;

                $onTime = $firstLog->started_at ? $firstLog->started_at->format('h:i A') : '';
                if (! empty($driverIn)) {
                    try {
                        $onTime = CarbonImmutable::parse((string) $driverIn)->format('h:i A');
                    } catch (\Throwable) {
                        $onTime = (string) $driverIn;
                    }
                }

                $offTime = $lastLog->ended_at ? $lastLog->ended_at->format('h:i A') : '';
                if (! empty($driverOut)) {
                    try {
                        $parsedOut = CarbonImmutable::parse((string) $driverOut);
                        $offTime = $parsedOut->format('h:i A');
                        if (! empty($driverIn)) {
                            $parsedIn = CarbonImmutable::parse((string) $driverIn);
                            if ($parsedOut->isAfter($parsedIn) && $parsedOut->toDateString() !== $parsedIn->toDateString()) {
                                $offTime .= ' (+1)';
                            }
                        }
                    } catch (\Throwable) {
                        $offTime = (string) $driverOut;
                    }
                } elseif ($lastLog->ended_at && $firstLog->started_at && $lastLog->ended_at->toDateString() !== $firstLog->started_at->toDateString()) {
                    $offTime .= ' (+1)';
                }

                $normMin = (int) $dayLogs->sum('normal_overtime_minutes');
                $doubMin = (int) $dayLogs->sum('double_overtime_minutes');
                $tripMin = (int) $dayLogs->sum('triple_overtime_minutes');

                $normHours = $normMin > 0 ? round($normMin / 60, 2) : 0.0;
                $doubHours = ($doubMin + $tripMin) > 0 ? round(($doubMin + $tripMin) / 60, 2) : 0.0;

                $totalNormalOtHours += $normHours;
                $totalDoubleOtHours += $doubHours;

                $dayOtHours = $normHours + $doubHours;
                $otType = $doubHours > 0 ? 'D' : ($normHours > 0 ? 'N' : '');

                $dayDriverOt = (float) $dayLogs->sum(fn ($l) => (float) ($l->metadata['driver_ot_amount'] ?? 0));
                $dayDriverSalary = (float) $dayLogs->sum(fn ($l) => (float) ($l->metadata['driver_salary_amount'] ?? 0));
                $totalDriverOtAmount += $dayDriverOt;
                $totalDriverSalaryAmount += $dayDriverSalary;

                $nightOut = (float) $dayLogs->sum('night_out_count');
                $totalNightOut += $nightOut;

                $garage = (float) $dayLogs->sum('garage_distance_km');
                $totalGarageKm += $garage;

                $pass = (float) $dayLogs->flatMap->events->whereIn('event_type', [
                    RentalUsageEventType::Pass,
                    RentalUsageEventType::Toll,
                    RentalUsageEventType::Parking,
                ])->sum('quantity');
                $totalPass += $pass;

                // Remarks
                $remarksParts = [];
                if ($firstLog->allocation && $firstLog->allocation->replaces_allocation_id !== null) {
                    $replVehicle = $firstLog->vehicle?->registration_number ?? $firstLog->vehicle?->vehicle_number ?? '';
                    $remarksParts[] = "For Repl. {$replVehicle}";
                }
                $logRemarks = $dayLogs->pluck('metadata.remarks')->filter()->unique()->all();
                if (! empty($logRemarks)) {
                    $remarksParts[] = implode('; ', $logRemarks);
                }
                $tripPurpose = $dayLogs->pluck('metadata.trip_purpose')->filter()->unique()->all();
                if (! empty($tripPurpose)) {
                    $remarksParts[] = implode('; ', $tripPurpose);
                }
                $remarks = implode(' - ', array_filter($remarksParts));

                $rows[] = [
                    'date' => $current->format('d/m/Y'),
                    'raw_date' => $dateStr,
                    'start_odometer' => $startOdo > 0 ? number_format($startOdo, 0, '.', '') : '0',
                    'finish_odometer' => $finishOdo > 0 ? number_format($finishOdo, 0, '.', '') : '0',
                    'comparative_mileage' => (int) round($compMileage),
                    'cumulative_mileage' => (int) round($cumulativeMileage),
                    'on_time' => $onTime,
                    'off_time' => $offTime,
                    'ot_hours' => $dayOtHours > 0 ? number_format($dayOtHours, 2, '.', '') : '',
                    'ot_type' => $otType,
                    'night_out' => $nightOut > 0 ? number_format($nightOut, 0, '.', '') : '',
                    'pass' => $pass > 0 ? number_format($pass, 2, '.', '') : '',
                    'raw_pass' => $pass,
                    'garage_km' => $garage,
                    'remarks' => $remarks,
                    'driver_ot_amount' => $dayDriverOt > 0 ? number_format($dayDriverOt, 2, '.', '') : '',
                    'driver_salary_amount' => $dayDriverSalary > 0 ? number_format($dayDriverSalary, 2, '.', '') : '',
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
                    'raw_pass' => 0.0,
                    'garage_km' => 0.0,
                    'remarks' => '',
                    'driver_ot_amount' => '',
                    'driver_salary_amount' => '',
                    'is_active_day' => false,
                ];
            }

            $current = $current->addDay();
        }

        $includedKm = (float) ($agreement->activeRateVersion?->included_km ?? 0);
        $finalCumulative = (int) round($cumulativeMileage);
        $excessKm = ($includedKm > 0 && $finalCumulative > $includedKm)
            ? (int) round($finalCumulative - $includedKm)
            : 0;

        $rentalModeLabel = match ($agreement->rental_mode) {
            RentalMode::WithDriver => 'WITH DRIVER MONTHLY BASIS',
            RentalMode::SelfDrive => 'SELF DRIVE MONTHLY BASIS',
            default => strtoupper(str_replace('_', ' ', $agreement->rental_mode->value ?? 'MONTHLY BASIS')),
        };

        return [
            'header' => [
                'company_name' => $agreement->tenant?->name ?? 'AT Tours & Transport (Pvt) Ltd',
                'report_title' => 'RUNNING CHART FOR LESSEE - Monthly Basis Vehicles',
                'rental_mode' => $rentalModeLabel,
                'client_name' => $agreement->customer?->name ?? 'N/A',
                'duration' => CarbonImmutable::parse($startDate)->format('d/m/Y').' To '.CarbonImmutable::parse($endDate)->format('d/m/Y'),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'page' => 1,
                'report_date' => now()->format('d/m/Y'),
                'agreement_number' => $agreement->agreement_number,
                'agreement_id' => $agreement->id,
                'agreement_vehicle_number' => $agreementVehicleNo,
            ],
            'rows' => $rows,
            'summary' => [
                'total_comparative_mileage' => (int) round($totalComparativeMileage),
                'final_cumulative_mileage' => $finalCumulative,
                'total_normal_ot_hours' => number_format($totalNormalOtHours, 2, '.', ''),
                'total_double_ot_hours' => number_format($totalDoubleOtHours, 2, '.', ''),
                'total_ot_hours' => number_format($totalNormalOtHours + $totalDoubleOtHours, 2, '.', ''),
                'total_night_out' => (int) $totalNightOut,
                'total_garage_km' => (int) $totalGarageKm,
                'total_pass' => number_format($totalPass, 2, '.', ''),
                'total_driver_ot_amount' => number_format($totalDriverOtAmount, 2, '.', ''),
                'total_driver_salary_amount' => number_format($totalDriverSalaryAmount, 2, '.', ''),
                'included_km' => (int) $includedKm,
                'excess_km' => $excessKm,
            ],
            'footer' => [
                'software_provider' => 'Software Provided by : AutoERP Computer Systems',
            ],
        ];
    }

    private function resolveAgreement(int $tenantId, ?int $organizationUnitId, array $params): RentalAgreement
    {
        if (! empty($params['agreement_id'])) {
            return RentalAgreement::query()
                ->where('tenant_id', $tenantId)
                ->with([
                    'customer',
                    'tenant',
                    'activeRateVersion.components',
                    'allocations.vehicle',
                    'allocations.replacesAllocation.vehicle',
                    'allocations.replacementAllocation.vehicle',
                ])
                ->findOrFail((int) $params['agreement_id']);
        }

        if (! empty($params['allocation_id'])) {
            $allocation = RentalVehicleAllocation::query()
                ->where('tenant_id', $tenantId)
                ->with([
                    'agreement.customer',
                    'agreement.tenant',
                    'agreement.activeRateVersion.components',
                    'agreement.allocations.vehicle',
                ])
                ->findOrFail((int) $params['allocation_id']);

            return $allocation->agreement;
        }

        throw new \InvalidArgumentException('Either agreement_id or allocation_id must be provided.');
    }
}
