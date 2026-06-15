<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Enums\RentalAgreementVehicleLinkStatus;
use Modules\VehicleRental\Enums\RentalAgreementVehicleStatus;
use Modules\VehicleRental\Enums\RentalPartyType;
use Modules\VehicleRental\Enums\RentalUsageLogStatus;
use Modules\VehicleRental\Http\Requests\ListRentalRequest;
use Modules\VehicleRental\Http\Requests\RunningChartContextRequest;
use Modules\VehicleRental\Http\Resources\RentalAgreementVehicleLinkResource;
use Modules\VehicleRental\Http\Resources\RentalRateSnapshotResource;
use Modules\VehicleRental\Models\RentalAgreementVehicle;
use Modules\VehicleRental\Models\RentalAgreementVehicleLink;
use Modules\VehicleRental\Models\RentalUsageLog;
use Modules\VehicleRental\Services\RentalUsageContextService;

final class RentalRunningChartController extends RentalController
{
    public function agreements(ListRentalRequest $request): JsonResponse
    {
        $search = trim((string) $request->input('search', ''));
        $allocations = RentalAgreementVehicle::query()
            ->forContext($request->tenantId(), $request->organizationUnitId())
            ->whereNotIn('status', [RentalAgreementVehicleStatus::Replaced->value])
            ->whereHas('agreement', fn (Builder $agreement) => $agreement->whereIn('status', [
                RentalAgreementStatus::Active->value,
                RentalAgreementStatus::Returned->value,
            ]))
            ->when($request->filled('agreement_id'), fn (Builder $query) => $query
                ->where('agreement_id', (int) $request->input('agreement_id')))
            ->when($request->filled('vehicle_id'), fn (Builder $query) => $query
                ->where('vehicle_id', (int) $request->input('vehicle_id')))
            ->when($request->filled('direction'), fn (Builder $query) => $query
                ->whereHas('agreement', fn (Builder $agreement) => $agreement
                    ->where('direction', (string) $request->input('direction'))))
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $scope) use ($search): void {
                $scope->whereHas('agreement', fn (Builder $agreement) => $agreement
                    ->where('agreement_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn (Builder $party) => $party
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('display_name', 'like', "%{$search}%"))
                    ->orWhereHas('supplier', fn (Builder $party) => $party
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('display_name', 'like', "%{$search}%")))
                    ->orWhereHas('vehicle', fn (Builder $vehicle) => $vehicle
                        ->where('registration_number', 'like', "%{$search}%")
                        ->orWhere('vehicle_number', 'like', "%{$search}%"));
            }))
            ->with([
                'agreement.customer',
                'agreement.supplier',
                'agreement.rateSnapshot',
                'vehicle.make',
                'vehicle.model',
            ])
            ->orderByDesc('allocated_from')
            ->orderByDesc('id')
            ->paginate($request->perPage());

        $rows = [];
        foreach ($allocations as $allocation) {
            $agreement = $allocation->agreement;
            if ($agreement === null) {
                continue;
            }
            $party = $agreement->party_type === RentalPartyType::Customer
                ? $agreement->customer
                : $agreement->supplier;
            $rows[] = [
                'agreement_id' => (int) $agreement->getKey(),
                'agreement_vehicle_id' => (int) $allocation->getKey(),
                'agreement_number' => $agreement->agreement_number,
                'direction' => $agreement->direction->value,
                'party_type' => $agreement->party_type->value,
                'party_id' => (int) $agreement->party_id,
                'party_name' => $party?->display_name ?? $party?->name,
                'vehicle_id' => (int) $allocation->vehicle_id,
                'vehicle_registration' => $allocation->vehicle?->registration_number
                    ?? $allocation->vehicle?->vehicle_number,
                'rental_type' => $agreement->rental_type->value,
                'billing_cycle' => $agreement->billing_cycle->value,
                'start_at' => $agreement->start_at?->toISOString(),
                'expected_end_at' => $agreement->expected_end_at?->toISOString(),
                'allocation_from' => $allocation->allocated_from?->toISOString(),
                'allocation_to' => $allocation->allocated_to?->toISOString(),
                'status' => $agreement->status->value,
            ];
        }

        return response()->json([
            'data' => $rows,
            'meta' => [
                'current_page' => $allocations->currentPage(),
                'from' => $allocations->firstItem(),
                'last_page' => $allocations->lastPage(),
                'path' => $allocations->path(),
                'per_page' => $allocations->perPage(),
                'to' => $allocations->lastItem(),
                'total' => $allocations->total(),
            ],
        ]);
    }

    public function context(
        RunningChartContextRequest $request,
        RentalUsageContextService $service,
    ): JsonResponse {
        $agreement = $this->agreement($request, (int) $request->input('agreement_id'))
            ->load(['rateSnapshot', 'customer', 'supplier']);
        $allocation = $this->allocation($agreement, (int) $request->input('agreement_vehicle_id'));
        $resolved = $service->resolve(
            $agreement,
            $allocation,
            (string) $request->input('usage_date'),
            $request->filled('start_time') ? (string) $request->input('start_time') : null,
        );
        $effectiveAt = CarbonImmutable::parse(
            (string) $request->input('usage_date').' '.
            ($request->filled('start_time') ? (string) $request->input('start_time') : '00:00:00'),
        );
        $lastApproved = RentalUsageLog::query()
            ->where('vehicle_id', $allocation->vehicle_id)
            ->where('status', RentalUsageLogStatus::Approved->value)
            ->where('effective_at', '<=', $effectiveAt)
            ->orderByDesc('effective_at')
            ->orderByDesc('operational_sequence')
            ->orderByDesc('id')
            ->first();
        $openLinks = RentalAgreementVehicleLink::query()
            ->forContext($request->tenantId(), $request->organizationUnitId())
            ->where('vehicle_id', $allocation->vehicle_id)
            ->whereIn('status', [
                RentalAgreementVehicleLinkStatus::Draft->value,
                RentalAgreementVehicleLinkStatus::Submitted->value,
                RentalAgreementVehicleLinkStatus::Approved->value,
            ])
            ->where(function (Builder $query) use ($allocation): void {
                $query->where('inbound_agreement_vehicle_id', $allocation->getKey())
                    ->orWhere('outbound_agreement_vehicle_id', $allocation->getKey());
            })
            ->where('effective_from', '<=', $effectiveAt)
            ->where('effective_to', '>', $effectiveAt)
            ->with([
                'vehicle.make',
                'vehicle.model',
                'inboundAgreement.supplier',
                'outboundAgreement.customer',
            ])
            ->get();
        if ($openLinks->count() > 1) {
            abort(409, 'Multiple open allocation links require correction before this running chart can continue.');
        }
        $openLink = $openLinks->first();

        return response()->json(['data' => [
            'vehicle_id' => (int) $allocation->vehicle_id,
            'vehicle' => $allocation->vehicle === null ? null : [
                'id' => (int) $allocation->vehicle->getKey(),
                'vehicle_number' => $allocation->vehicle->vehicle_number,
                'registration_number' => $allocation->vehicle->registration_number,
                'odometer_reading' => (string) $allocation->vehicle->odometer_reading,
            ],
            'selected_agreement_id' => (int) $agreement->getKey(),
            'agreement_vehicle_link_id' => $resolved['link']?->getKey(),
            'agreement_vehicle_link' => $openLink === null
                ? null
                : (new RentalAgreementVehicleLinkResource($openLink))->resolve($request),
            'last_valid_finish_odometer' => (string) ($lastApproved?->end_odometer
                ?? $allocation->pickupInspection?->odometer
                ?? $allocation->start_odometer),
            'approved_cumulative_mileage' => (string) ($lastApproved?->cumulative_km ?? '0.000000'),
            'contexts' => collect($resolved['contexts'])->map(function (array $row) use ($request): array {
                $contextAgreement = $row['agreement'];
                $party = $contextAgreement->party_type === RentalPartyType::Customer
                    ? $contextAgreement->customer
                    : $contextAgreement->supplier;

                return [
                    'agreement_id' => (int) $contextAgreement->getKey(),
                    'agreement_vehicle_id' => (int) $row['allocation']->getKey(),
                    'agreement_number' => $contextAgreement->agreement_number,
                    'direction' => $contextAgreement->direction->value,
                    'financial_side' => $contextAgreement->direction->value === 'outbound' ? 'revenue' : 'cost',
                    'party_type' => $contextAgreement->party_type->value,
                    'party_id' => (int) $contextAgreement->party_id,
                    'party_name' => $party?->display_name ?? $party?->name,
                    'billing_cycle' => $contextAgreement->billing_cycle->value,
                    'currency_id' => $contextAgreement->currency_id,
                    'rate_snapshot' => (new RentalRateSnapshotResource($contextAgreement->rateSnapshot))
                        ->resolve($request),
                ];
            })->values()->all(),
        ]]);
    }
}
