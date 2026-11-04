<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Modules\Core\Services\DecimalMath;
use Modules\Tenant\Models\TenantModel;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\Constants\OperationalFields;
use Modules\VehicleRental\Data\AgreementContext;
use Modules\VehicleRental\Enums\AgreementStatus;
use Modules\VehicleRental\Enums\RentalExpenseStatus;
use Modules\VehicleRental\Enums\RentalExpenseType;
use Modules\VehicleRental\Enums\RunningChartStatus;
use Modules\VehicleRental\Models\CustomerBaseCharge;
use Modules\VehicleRental\Models\CustomerUsageCharge;
use Modules\VehicleRental\Models\OwnerAgreement;
use Modules\VehicleRental\Models\OwnerBaseCharge;
use Modules\VehicleRental\Models\OwnerUsageCharge;
use Modules\VehicleRental\Models\RentalExpense;
use Modules\VehicleRental\Models\RunningChart;
use Modules\VehicleRental\Models\VehicleUse;

final class VehicleProfitabilityReportService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly RentalAuthorization $authorization,
    ) {}

    /**
     * @param  array{start_date: string, end_date: string, ownership_type?: ?string, vehicle_id?: ?int}  $params
     * @return array<string, mixed>
     */
    public function generate(AgreementContext $context, array $params): array
    {
        $this->authorization->assertReport($context);

        if (empty($params['start_date']) || empty($params['end_date'])) {
            throw ValidationException::withMessages(['start_date' => ['Specify both start date and end date for the profitability report.']]);
        }

        $startDate = (string) $params['start_date'];
        $endDate = (string) $params['end_date'];

        $start = CarbonImmutable::parse($startDate)->startOfDay();
        $end = CarbonImmutable::parse($endDate)->endOfDay();

        if ($end->lessThan($start)) {
            throw ValidationException::withMessages(['end_date' => ['End date must be on or after start date.']]);
        }

        $ownershipFilter = (string) ($params['ownership_type'] ?? 'all');
        $selectedVehicleId = ! empty($params['vehicle_id']) ? (int) $params['vehicle_id'] : null;

        $vehicles = Vehicle::query()
            ->where('tenant_id', $context->tenantId)
            ->when($selectedVehicleId !== null, fn (Builder $q) => $q->where('id', $selectedVehicleId))
            ->orderBy('vehicle_number')
            ->get();

        $ownerAgreements = OwnerAgreement::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->where('starts_on', '<=', $endDate)
            ->where(fn (Builder $q) => $q->whereNull('ends_on')->orWhere('ends_on', '>=', $startDate))
            ->whereIn('status', [AgreementStatus::Active->value, AgreementStatus::Closed->value])
            ->get()
            ->groupBy('vehicle_id');

        $vehicleUses = VehicleUse::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->where('starts_at', '<=', $end->format(OperationalFields::DATABASE_TIMESTAMP_FORMAT))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $start->format(OperationalFields::DATABASE_TIMESTAMP_FORMAT)))
            ->get()
            ->groupBy('vehicle_id');

        $charts = RunningChart::query()
            ->forTenant($context->tenantId)
            ->where('starts_at', '>=', $start->format(OperationalFields::DATABASE_TIMESTAMP_FORMAT))
            ->where('starts_at', '<=', $end->format(OperationalFields::DATABASE_TIMESTAMP_FORMAT))
            ->whereIn('status', [RunningChartStatus::Draft->value, RunningChartStatus::Finalized->value])
            ->with('vehicleUse')
            ->get()
            ->groupBy(fn (RunningChart $c): int => (int) $c->vehicleUse?->vehicle_id);

        $expenses = RentalExpense::query()
            ->forTenant($context->tenantId)
            ->where('expense_date', '>=', $startDate)
            ->where('expense_date', '<=', $endDate)
            ->whereNotIn('status', [RentalExpenseStatus::Rejected->value, RentalExpenseStatus::Reversed->value])
            ->get()
            ->groupBy('vehicle_id');

        $rows = [];
        $fleetTotalRevenue = 0.0;
        $fleetTotalDirectCosts = 0.0;
        $fleetTotalMargin = 0.0;
        $fleetTotalDistance = 0.0;

        foreach ($vehicles as $vehicle) {
            $isOwnerLeased = $ownerAgreements->has($vehicle->id);

            if ($ownershipFilter === 'company' && $isOwnerLeased) {
                continue;
            }
            if ($ownershipFilter === 'owner_leased' && ! $isOwnerLeased) {
                continue;
            }

            $uses = $vehicleUses->get($vehicle->id) ?? collect();
            $customerAgreementIds = $uses->pluck('customer_agreement_id')->unique()->filter()->all();

            // Customer Revenue
            $vehicleCustomerBase = empty($customerAgreementIds) ? 0.0 : (float) CustomerBaseCharge::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->whereIn('agreement_id', $customerAgreementIds)
                ->whereNull('voided_at')
                ->where('period_from', '<=', $endDate)
                ->where('period_until', '>=', $startDate)
                ->sum('amount');

            $vehicleCustomerUsage = empty($customerAgreementIds) ? 0.0 : (float) CustomerUsageCharge::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->whereIn('agreement_id', $customerAgreementIds)
                ->whereNull('voided_at')
                ->where('period_from', '<=', $endDate)
                ->where('period_until', '>=', $startDate)
                ->sum('amount');

            $vehicleRevenue = $vehicleCustomerBase + $vehicleCustomerUsage;

            // Direct Costs
            $ownerAgreementIds = $ownerAgreements->get($vehicle->id)?->pluck('id')->all() ?? [];
            $ownerLeaseCost = 0.0;
            if (! empty($ownerAgreementIds)) {
                $ownerBase = (float) OwnerBaseCharge::query()
                    ->forContext($context->tenantId, $context->organizationUnitId)
                    ->whereIn('agreement_id', $ownerAgreementIds)
                    ->whereNull('voided_at')
                    ->where('period_from', '<=', $endDate)
                    ->where('period_until', '>=', $startDate)
                    ->sum('amount');

                $ownerUsage = (float) OwnerUsageCharge::query()
                    ->forContext($context->tenantId, $context->organizationUnitId)
                    ->whereIn('agreement_id', $ownerAgreementIds)
                    ->whereNull('voided_at')
                    ->where('period_from', '<=', $endDate)
                    ->where('period_until', '>=', $startDate)
                    ->sum('amount');

                $ownerLeaseCost = $ownerBase + $ownerUsage;
            }

            $vehicleExpenses = $expenses->get($vehicle->id) ?? collect();
            $fuelCost = (float) $vehicleExpenses->where('expense_type', RentalExpenseType::Fuel)->sum('gross_amount');
            $maintenanceCost = (float) $vehicleExpenses->where('expense_type', RentalExpenseType::Maintenance)->sum('gross_amount');
            $tollCost = (float) $vehicleExpenses->where('expense_type', RentalExpenseType::Toll)->sum('gross_amount');
            $otherCost = (float) $vehicleExpenses->whereIn('expense_type', [RentalExpenseType::Fine, RentalExpenseType::Other])->sum('gross_amount');

            $totalDirectCosts = $ownerLeaseCost + $fuelCost + $maintenanceCost + $tollCost + $otherCost;
            $grossMargin = $vehicleRevenue - $totalDirectCosts;
            $marginPercentage = $vehicleRevenue > 0 ? round(($grossMargin / $vehicleRevenue) * 100, 1) : 0.0;

            // Distance
            $vehicleCharts = $charts->get($vehicle->id) ?? collect();
            $distanceKm = 0.0;
            foreach ($vehicleCharts as $c) {
                if ($c->commercial_km !== null) {
                    $distanceKm += (float) $c->commercial_km;
                } elseif ($c->start_odometer !== null && $c->end_odometer !== null) {
                    $distanceKm += max(0.0, (float) $c->end_odometer - (float) $c->start_odometer);
                }
            }

            $revenuePerKm = $distanceKm > 0 ? round($vehicleRevenue / $distanceKm, 2) : 0.0;
            $costPerKm = $distanceKm > 0 ? round($totalDirectCosts / $distanceKm, 2) : 0.0;

            $fleetTotalRevenue += $vehicleRevenue;
            $fleetTotalDirectCosts += $totalDirectCosts;
            $fleetTotalMargin += $grossMargin;
            $fleetTotalDistance += $distanceKm;

            $rows[] = [
                'vehicle_id' => $vehicle->id,
                'registration_number' => $vehicle->registration_number ?? $vehicle->vehicle_number,
                'vehicle_make_model' => trim(($vehicle->make ?? '').' '.($vehicle->model ?? '')) ?: 'N/A',
                'ownership_type' => $isOwnerLeased ? 'Owner Leased' : 'Company Owned',
                'customer_revenue' => number_format($vehicleRevenue, 2, '.', ''),
                'owner_lease_cost' => number_format($ownerLeaseCost, 2, '.', ''),
                'fuel_cost' => number_format($fuelCost, 2, '.', ''),
                'maintenance_cost' => number_format($maintenanceCost, 2, '.', ''),
                'toll_cost' => number_format($tollCost, 2, '.', ''),
                'other_cost' => number_format($otherCost, 2, '.', ''),
                'total_direct_costs' => number_format($totalDirectCosts, 2, '.', ''),
                'gross_margin' => number_format($grossMargin, 2, '.', ''),
                'margin_percentage' => $marginPercentage,
                'distance_km' => (int) round($distanceKm),
                'revenue_per_km' => number_format($revenuePerKm, 2, '.', ''),
                'cost_per_km' => number_format($costPerKm, 2, '.', ''),
            ];
        }

        $fleetMarginPercentage = $fleetTotalRevenue > 0 ? round(($fleetTotalMargin / $fleetTotalRevenue) * 100, 1) : 0.0;
        $fleetAvgRevenuePerKm = $fleetTotalDistance > 0 ? round($fleetTotalRevenue / $fleetTotalDistance, 2) : 0.0;
        $fleetAvgCostPerKm = $fleetTotalDistance > 0 ? round($fleetTotalDirectCosts / $fleetTotalDistance, 2) : 0.0;

        $tenantName = TenantModel::query()->find($context->tenantId)?->name ?? (config('app.name') ?: 'AutoERP');

        return [
            'header' => [
                'company_name' => $tenantName,
                'report_title' => 'FLEET VEHICLE PROFITABILITY & PERFORMANCE REPORT',
                'duration' => $start->format('d/m/Y').' To '.$end->format('d/m/Y'),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'report_date' => now()->format('d/m/Y'),
            ],
            'filters' => [
                'ownership_type' => $ownershipFilter,
            ],
            'summary' => [
                'total_vehicles_evaluated' => count($rows),
                'fleet_total_revenue' => number_format($fleetTotalRevenue, 2, '.', ''),
                'fleet_total_direct_costs' => number_format($fleetTotalDirectCosts, 2, '.', ''),
                'fleet_gross_margin' => number_format($fleetTotalMargin, 2, '.', ''),
                'fleet_margin_percentage' => $fleetMarginPercentage,
                'fleet_total_distance_km' => (int) round($fleetTotalDistance),
                'fleet_avg_revenue_per_km' => number_format($fleetAvgRevenuePerKm, 2, '.', ''),
                'fleet_avg_cost_per_km' => number_format($fleetAvgCostPerKm, 2, '.', ''),
            ],
            'rows' => $rows,
            'footer' => [
                'software_provider' => 'Software Provided by : AutoERP Computer Systems',
            ],
        ];
    }
}
