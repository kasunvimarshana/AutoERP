<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\Enums\AgreementStatus;
use Modules\VehicleRental\Enums\IncidentStatus;
use Modules\VehicleRental\Enums\RentalExpenseStatus;
use Modules\VehicleRental\Enums\RentalReservationStatus;
use Modules\VehicleRental\Enums\RunningChartStatus;
use Modules\VehicleRental\Enums\VehicleFinanceInstallmentStatus;
use Modules\VehicleRental\Enums\VehicleUseStatus;
use Modules\VehicleRental\Models\CustomerAgreement;
use Modules\VehicleRental\Models\OwnerAgreement;
use Modules\VehicleRental\Models\RentalExpense;
use Modules\VehicleRental\Models\RentalIncident;
use Modules\VehicleRental\Models\RentalReservation;
use Modules\VehicleRental\Models\RunningChart;
use Modules\VehicleRental\Models\VehicleFinanceInstallment;
use Modules\VehicleRental\Models\VehicleUse;

final class RentalDashboardService
{
    public function getSummary(int $tenantId, ?int $organizationUnitId): array
    {
        $activeCustomerAgreements = CustomerAgreement::query()
            ->forContext($tenantId, $organizationUnitId)
            ->where('status', AgreementStatus::Active->value)
            ->count();

        $activeOwnerAgreements = OwnerAgreement::query()
            ->forContext($tenantId, $organizationUnitId)
            ->where('status', AgreementStatus::Active->value)
            ->count();

        $vehiclesInCustody = VehicleUse::query()
            ->forContext($tenantId, $organizationUnitId)
            ->where('status', VehicleUseStatus::InCustody->value)
            ->count();

        $plannedUses = VehicleUse::query()
            ->forContext($tenantId, $organizationUnitId)
            ->where('status', VehicleUseStatus::Planned->value)
            ->count();

        $pendingReservations = RentalReservation::query()
            ->forContext($tenantId, $organizationUnitId)
            ->where('status', RentalReservationStatus::Pending->value)
            ->count();

        $confirmedReservations = RentalReservation::query()
            ->forContext($tenantId, $organizationUnitId)
            ->where('status', RentalReservationStatus::Confirmed->value)
            ->count();

        $dueInstallments = VehicleFinanceInstallment::query()
            ->forContext($tenantId, $organizationUnitId)
            ->whereIn('status', [VehicleFinanceInstallmentStatus::Due->value, VehicleFinanceInstallmentStatus::Overdue->value])
            ->count();

        $pendingExpenses = RentalExpense::query()
            ->forContext($tenantId, $organizationUnitId)
            ->whereIn('status', [RentalExpenseStatus::Draft->value, RentalExpenseStatus::Submitted->value])
            ->count();

        $activeIncidents = RentalIncident::query()
            ->forContext($tenantId, $organizationUnitId)
            ->where('status', IncidentStatus::Recorded->value)
            ->count();

        $totalFleetVehicles = Vehicle::query()->forTenant($tenantId)->count();
        $availableFleetVehicles = max(0, $totalFleetVehicles - $vehiclesInCustody);

        $recentRunningCharts = RunningChart::query()
            ->forContext($tenantId, $organizationUnitId)
            ->with(['use.vehicle', 'customerAgreement'])
            ->latest('ends_at')
            ->limit(5)
            ->get()
            ->map(fn (RunningChart $c) => [
                'id' => $c->id,
                'reference' => $c->reference,
                'vehicle_label' => $c->use?->vehicle ? ($c->use->vehicle->registration_number ?? $c->use->vehicle->vehicle_number) : 'Vehicle',
                'customer_reference' => $c->customerAgreement?->reference,
                'starts_at' => $c->starts_at->toIso8601String(),
                'ends_at' => $c->ends_at->toIso8601String(),
                'total_km' => $c->total_km,
                'status' => $c->status instanceof RunningChartStatus ? $c->status->value : $c->status,
            ]);

        return [
            'metrics' => [
                'active_customer_agreements' => $activeCustomerAgreements,
                'active_owner_agreements' => $activeOwnerAgreements,
                'vehicles_in_custody' => $vehiclesInCustody,
                'planned_vehicle_uses' => $plannedUses,
                'pending_reservations' => $pendingReservations,
                'confirmed_reservations' => $confirmedReservations,
                'due_finance_installments' => $dueInstallments,
                'pending_expenses' => $pendingExpenses,
                'active_vehicle_incidents' => $activeIncidents,
                'total_fleet' => $totalFleetVehicles,
                'available_fleet' => $availableFleetVehicles,
            ],
            'recent_running_charts' => $recentRunningCharts,
        ];
    }
}
