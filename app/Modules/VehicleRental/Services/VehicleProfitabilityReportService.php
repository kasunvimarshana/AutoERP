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
use Modules\VehicleRental\Enums\RentalExpenseAllocationStatus;
use Modules\VehicleRental\Enums\RentalExpenseAllocationType;
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
            ->with('allocations')
            ->get()
            ->groupBy('vehicle_id');

        $rows = [];
        $fleetTotalRevenue = '0.00';
        $fleetTotalDirectCosts = '0.00';
        $fleetTotalMargin = '0.00';
        $fleetTotalDistance = '0.000';

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
            $vehicleCustomerBase = empty($customerAgreementIds) ? '0.00' : (string) CustomerBaseCharge::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->whereIn('agreement_id', $customerAgreementIds)
                ->whereNull('voided_at')
                ->where('period_from', '<=', $endDate)
                ->where('period_until', '>=', $startDate)
                ->sum('amount');

            $vehicleCustomerUsage = empty($customerAgreementIds) ? '0.00' : (string) CustomerUsageCharge::query()
                ->forContext($context->tenantId, $context->organizationUnitId)
                ->whereIn('agreement_id', $customerAgreementIds)
                ->whereNull('voided_at')
                ->where('period_from', '<=', $endDate)
                ->where('period_until', '>=', $startDate)
                ->sum('amount');

            $vehicleExpenses = $expenses->get($vehicle->id) ?? collect();
            $customerRecoveries = '0.00';
            $ownerDeductions = '0.00';
            foreach ($vehicleExpenses as $exp) {
                foreach ($exp->allocations as $alloc) {
                    if ($alloc->status === RentalExpenseAllocationStatus::Confirmed) {
                        if ($alloc->allocation_type === RentalExpenseAllocationType::CustomerRecovery) {
                            $customerRecoveries = $this->math->add($customerRecoveries, (string) $alloc->total_amount, 2);
                        } elseif ($alloc->allocation_type === RentalExpenseAllocationType::OwnerDeduction) {
                            $ownerDeductions = $this->math->add($ownerDeductions, (string) $alloc->total_amount, 2);
                        }
                    }
                }
            }

            // Customer Revenue includes base, usage, and recovered charges
            $vehicleRevenue = $this->math->sum([$vehicleCustomerBase, $vehicleCustomerUsage, $customerRecoveries], 2);

            // Direct Costs
            $ownerAgreementIds = $ownerAgreements->get($vehicle->id)?->pluck('id')->all() ?? [];
            $ownerLeaseCost = '0.00';
            if (! empty($ownerAgreementIds)) {
                $ownerBase = (string) OwnerBaseCharge::query()
                    ->forContext($context->tenantId, $context->organizationUnitId)
                    ->whereIn('agreement_id', $ownerAgreementIds)
                    ->whereNull('voided_at')
                    ->where('period_from', '<=', $endDate)
                    ->where('period_until', '>=', $startDate)
                    ->sum('amount');

                $ownerUsage = (string) OwnerUsageCharge::query()
                    ->forContext($context->tenantId, $context->organizationUnitId)
                    ->whereIn('agreement_id', $ownerAgreementIds)
                    ->whereNull('voided_at')
                    ->where('period_from', '<=', $endDate)
                    ->where('period_until', '>=', $startDate)
                    ->sum('amount');

                // Net of owner deductions (maintenance, tyre, fuel chargebacks)
                $grossOwner = $this->math->add($ownerBase, $ownerUsage, 2);
                $ownerLeaseCost = bccomp($grossOwner, $ownerDeductions, 2) > 0
                    ? $this->math->sub($grossOwner, $ownerDeductions, 2)
                    : '0.00';
            }

            $fuelCost = (string) $vehicleExpenses->where('expense_type', RentalExpenseType::Fuel)->sum('gross_amount');
            $maintenanceCost = (string) $vehicleExpenses->where('expense_type', RentalExpenseType::Maintenance)->sum('gross_amount');
            $tollCost = (string) $vehicleExpenses->where('expense_type', RentalExpenseType::Toll)->sum('gross_amount');
            $otherCost = (string) $vehicleExpenses->whereIn('expense_type', [RentalExpenseType::Fine, RentalExpenseType::Other])->sum('gross_amount');

            $totalDirectCosts = $this->math->sum([$ownerLeaseCost, $fuelCost, $maintenanceCost, $tollCost, $otherCost], 2);
            $grossMargin = $this->math->sub($vehicleRevenue, $totalDirectCosts, 2);
            $marginPercentage = bccomp($vehicleRevenue, '0', 2) > 0
                ? round((float) $this->math->div($this->math->mul($grossMargin, '100', 4), $vehicleRevenue, 4), 1)
                : 0.0;

            // Distance
            $vehicleCharts = $charts->get($vehicle->id) ?? collect();
            $distanceKm = '0.000';
            foreach ($vehicleCharts as $c) {
                if ($c->commercial_km !== null) {
                    $distanceKm = $this->math->add($distanceKm, (string) $c->commercial_km, 3);
                } elseif ($c->start_odometer !== null && $c->end_odometer !== null) {
                    $diff = $this->math->sub((string) $c->end_odometer, (string) $c->start_odometer, 3);
                    if (bccomp($diff, '0', 3) > 0) {
                        $distanceKm = $this->math->add($distanceKm, $diff, 3);
                    }
                }
            }

            $revenuePerKm = bccomp($distanceKm, '0', 3) > 0 ? $this->math->div($vehicleRevenue, $distanceKm, 2) : '0.00';
            $costPerKm = bccomp($distanceKm, '0', 3) > 0 ? $this->math->div($totalDirectCosts, $distanceKm, 2) : '0.00';

            $fleetTotalRevenue = $this->math->add($fleetTotalRevenue, $vehicleRevenue, 2);
            $fleetTotalDirectCosts = $this->math->add($fleetTotalDirectCosts, $totalDirectCosts, 2);
            $fleetTotalMargin = $this->math->add($fleetTotalMargin, $grossMargin, 2);
            $fleetTotalDistance = $this->math->add($fleetTotalDistance, $distanceKm, 3);

            $rows[] = [
                'vehicle_id' => $vehicle->id,
                'registration_number' => $vehicle->registration_number ?? $vehicle->vehicle_number,
                'vehicle_make_model' => trim(($vehicle->make ?? '').' '.($vehicle->model ?? '')) ?: 'N/A',
                'ownership_type' => $isOwnerLeased ? 'Owner Leased' : 'Company Owned',
                'customer_revenue' => number_format((float) $vehicleRevenue, 2, '.', ''),
                'owner_lease_cost' => number_format((float) $ownerLeaseCost, 2, '.', ''),
                'fuel_cost' => number_format((float) $fuelCost, 2, '.', ''),
                'maintenance_cost' => number_format((float) $maintenanceCost, 2, '.', ''),
                'toll_cost' => number_format((float) $tollCost, 2, '.', ''),
                'other_cost' => number_format((float) $otherCost, 2, '.', ''),
                'total_direct_costs' => number_format((float) $totalDirectCosts, 2, '.', ''),
                'gross_margin' => number_format((float) $grossMargin, 2, '.', ''),
                'margin_percentage' => $marginPercentage,
                'distance_km' => (int) round((float) $distanceKm),
                'revenue_per_km' => $revenuePerKm,
                'cost_per_km' => $costPerKm,
            ];
        }

        $fleetMarginPercentage = bccomp($fleetTotalRevenue, '0', 2) > 0
            ? round((float) $this->math->div($this->math->mul($fleetTotalMargin, '100', 4), $fleetTotalRevenue, 4), 1)
            : 0.0;
        $fleetAvgRevenuePerKm = bccomp($fleetTotalDistance, '0', 3) > 0
            ? $this->math->div($fleetTotalRevenue, $fleetTotalDistance, 2)
            : '0.00';
        $fleetAvgCostPerKm = bccomp($fleetTotalDistance, '0', 3) > 0
            ? $this->math->div($fleetTotalDirectCosts, $fleetTotalDistance, 2)
            : '0.00';

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
                'fleet_total_revenue' => number_format((float) $fleetTotalRevenue, 2, '.', ''),
                'fleet_total_direct_costs' => number_format((float) $fleetTotalDirectCosts, 2, '.', ''),
                'fleet_gross_margin' => number_format((float) $fleetTotalMargin, 2, '.', ''),
                'fleet_margin_percentage' => $fleetMarginPercentage,
                'fleet_total_distance_km' => (int) round((float) $fleetTotalDistance),
                'fleet_avg_revenue_per_km' => number_format((float) $fleetAvgRevenuePerKm, 2, '.', ''),
                'fleet_avg_cost_per_km' => number_format((float) $fleetAvgCostPerKm, 2, '.', ''),
            ],
            'rows' => $rows,
            'footer' => [
                'software_provider' => 'Software Provided by : AutoERP Computer Systems',
            ],
        ];
    }
}
