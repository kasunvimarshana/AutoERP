<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Tenant\Models\TenantModel;
use Modules\VehicleRental\Enums\RentalAgreementKind;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Enums\RentalAllocationStatus;
use Modules\VehicleRental\Enums\RentalBillingBasis;
use Modules\VehicleRental\Enums\RentalBillingCycle;
use Modules\VehicleRental\Enums\RentalCalculationStatus;
use Modules\VehicleRental\Enums\RentalCustodyEventType;
use Modules\VehicleRental\Enums\RentalExcessKmMethod;
use Modules\VehicleRental\Enums\RentalExpenseAllocationType;
use Modules\VehicleRental\Enums\RentalExpenseType;
use Modules\VehicleRental\Enums\RentalFinancialSide;
use Modules\VehicleRental\Enums\RentalMode;
use Modules\VehicleRental\Enums\RentalProrationRule;
use Modules\VehicleRental\Enums\RentalRateComponentCode;
use Modules\VehicleRental\Enums\RentalRateUnit;
use Modules\VehicleRental\Enums\RentalUsageEventApplicability;
use Modules\VehicleRental\Enums\RentalUsageEventType;
use Modules\VehicleRental\Enums\RentalUsageStatus;
use Modules\VehicleRental\Enums\RentalVehicleSourceType;
use Modules\VehicleRental\Http\Requests\ListRentalRequest;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalCalculationRun;
use Modules\VehicleRental\Models\RentalUsageLog;
use Modules\VehicleRental\Models\RentalVehicleAllocation;
use Modules\VehicleRental\Services\RentalUsageEventBillingMap;
use Modules\VehicleRental\Services\VehicleRentalAuthorizationService;

final class RentalContextController
{
    public function __construct(private readonly VehicleRentalAuthorizationService $authorization) {}

    public function metadata(ListRentalRequest $request): JsonResponse
    {
        $this->authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            VehicleRentalAuthorizationService::VIEW,
        );

        $values = static fn (array $cases): array => array_map(
            static fn ($case): string => $case->value,
            $cases,
        );

        $tenant = TenantModel::query()
            ->with('baseCurrency')
            ->findOrFail($request->tenantId());

        return response()->json(['data' => [
            'default_currency' => $this->currencySummary($tenant->baseCurrency),
            'agreement_kinds' => $values(RentalAgreementKind::cases()),
            'agreement_statuses' => $values(RentalAgreementStatus::cases()),
            'allocation_statuses' => $values(RentalAllocationStatus::cases()),
            'rental_modes' => $values(RentalMode::cases()),
            'billing_cycles' => $values(RentalBillingCycle::cases()),
            'billing_bases' => $values(RentalBillingBasis::cases()),
            'proration_rules' => $values(RentalProrationRule::cases()),
            'excess_km_methods' => $values(RentalExcessKmMethod::cases()),
            'vehicle_source_types' => $values(RentalVehicleSourceType::cases()),
            'custody_event_types' => $values(RentalCustodyEventType::cases()),
            'usage_event_types' => $values(RentalUsageEventType::cases()),
            'usage_event_rate_components' => RentalUsageEventBillingMap::eventComponentCodes(),
            'usage_event_applicabilities' => $values(RentalUsageEventApplicability::cases()),
            'expense_types' => $values(RentalExpenseType::cases()),
            'expense_allocation_types' => $values(RentalExpenseAllocationType::cases()),
            'financial_sides' => $values(RentalFinancialSide::cases()),
            'rate_component_codes' => $values(RentalRateComponentCode::cases()),
            'rate_units' => $values(RentalRateUnit::cases()),
        ]]);
    }

    public function dashboard(ListRentalRequest $request): JsonResponse
    {
        $this->authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            VehicleRentalAuthorizationService::VIEW,
        );
        $scope = static fn ($query) => $query->forContext(
            $request->tenantId(),
            $request->organizationUnitId(),
        );

        return response()->json(['data' => [
            'active_agreements' => $scope(RentalAgreement::query())
                ->where('status', RentalAgreementStatus::Active->value)
                ->count(),
            'active_allocations' => $scope(RentalVehicleAllocation::query())
                ->where('status', RentalAllocationStatus::Active->value)
                ->count(),
            'usage_pending_approval' => $scope(RentalUsageLog::query())
                ->where('status', RentalUsageStatus::Submitted->value)
                ->count(),
            'calculations_pending_approval' => $scope(RentalCalculationRun::query())
                ->where('calculation_status', RentalCalculationStatus::Submitted->value)
                ->count(),
        ]]);
    }

    private function currencySummary(?CurrencyModel $currency): ?array
    {
        return $currency === null ? null : [
            'id' => (int) $currency->getKey(),
            'code' => $currency->code,
            'name' => $currency->name,
            'symbol' => $currency->symbol,
        ];
    }
}
