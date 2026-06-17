<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Enums\RentalAgreementVehicleStatus;
use Modules\VehicleRental\Enums\RentalPartyType;
use Modules\VehicleRental\Enums\RentalUsageEventType;
use Modules\VehicleRental\Enums\RentalUsageLogStatus;
use Modules\VehicleRental\Http\Requests\ListRentalRequest;
use Modules\VehicleRental\Http\Requests\RentalActionRequest;
use Modules\VehicleRental\Http\Requests\RunningChartPreviewRequest;
use Modules\VehicleRental\Http\Requests\RunningChartContextRequest;
use Modules\VehicleRental\Http\Requests\StoreRunningChartTripRequest;
use Modules\VehicleRental\Http\Requests\SubmitRunningChartDailyRequest;
use Modules\VehicleRental\Http\Resources\RentalAgreementVehicleLinkResource;
use Modules\VehicleRental\Http\Resources\RentalRateSnapshotResource;
use Modules\VehicleRental\Http\Resources\RentalUsageLogResource;
use Modules\VehicleRental\Models\RentalAgreementVehicle;
use Modules\VehicleRental\Models\RentalAgreementVehicleLink;
use Modules\VehicleRental\Models\RentalUsageLog;
use Modules\VehicleRental\Services\RentalChargeCalculationService;
use Modules\VehicleRental\Services\RentalUsageContextService;
use Modules\VehicleRental\Services\RentalUsageEventService;
use Modules\VehicleRental\Services\RentalUsageLogService;
use Modules\VehicleRental\Services\VehicleRentalAuthorizationService;

final class RentalRunningChartController extends RentalController
{
    public function agreements(ListRentalRequest $request): JsonResponse
    {
        $search = trim((string) $request->input('search', ''));
        $direction = $request->filled('direction') ? (string) $request->input('direction') : null;
        $mode = $request->filled('mode') ? (string) $request->input('mode') : null;
        $side = $request->filled('side') ? (string) $request->input('side') : null;
        $usageDay = $request->filled('usage_date')
            ? CarbonImmutable::parse((string) $request->input('usage_date'))->startOfDay()
            : null;
        if ($direction === null) {
            $direction = match (true) {
                $mode === RentalUsageContextService::MODE_LESSEE => 'outbound',
                $mode === RentalUsageContextService::MODE_LESSOR => 'inbound',
                $mode === RentalUsageContextService::MODE_LINKED && $side === 'lessor' => 'inbound',
                $mode === RentalUsageContextService::MODE_LINKED => 'outbound',
                default => null,
            };
        }
        $allocations = RentalAgreementVehicle::query()
            ->forContext($request->tenantId(), $request->organizationUnitId())
            ->whereNotIn('status', [RentalAgreementVehicleStatus::Replaced->value])
            ->whereHas('agreement', fn (Builder $agreement) => $agreement->whereIn('status', [
                RentalAgreementStatus::Active->value,
                RentalAgreementStatus::Returned->value,
            ]))
            ->when($usageDay !== null, fn (Builder $query) => $query
                ->where('allocated_from', '<', $usageDay->addDay())
                ->where(function (Builder $scope) use ($usageDay): void {
                    $scope->where('allocated_to', '>', $usageDay)
                        ->orWhere(function (Builder $openEnded) use ($usageDay): void {
                            $openEnded->whereNull('allocated_to')
                                ->whereHas('agreement', fn (Builder $agreement) => $agreement
                                    ->where('expected_end_at', '>', $usageDay));
                        });
                }))
            ->when($request->filled('agreement_id'), fn (Builder $query) => $query
                ->where('agreement_id', (int) $request->input('agreement_id')))
            ->when($request->filled('vehicle_id'), fn (Builder $query) => $query
                ->where('vehicle_id', (int) $request->input('vehicle_id')))
            ->when($direction !== null, fn (Builder $query) => $query
                ->whereHas('agreement', fn (Builder $agreement) => $agreement
                    ->where('direction', $direction)))
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
        $resolved = $this->resolveRunningChart($request, $service);
        $agreement = $resolved['selected_agreement'];
        $allocation = $resolved['selected_allocation'];
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
        $link = $resolved['link'];
        $link?->loadMissing([
            'vehicle.make',
            'vehicle.model',
            'inboundAgreement.supplier',
            'outboundAgreement.customer',
        ]);

        return response()->json(['data' => [
            'mode' => $request->mode(),
            'vehicle_id' => (int) $allocation->vehicle_id,
            'vehicle' => $allocation->vehicle === null ? null : [
                'id' => (int) $allocation->vehicle->getKey(),
                'vehicle_number' => $allocation->vehicle->vehicle_number,
                'registration_number' => $allocation->vehicle->registration_number,
                'odometer_reading' => (string) $allocation->vehicle->odometer_reading,
            ],
            'selected_agreement_id' => (int) $agreement->getKey(),
            'agreement_vehicle_link_id' => $resolved['link']?->getKey(),
            'agreement_vehicle_link' => $link === null
                ? null
                : (new RentalAgreementVehicleLinkResource($link))->resolve($request),
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

    public function trips(
        RunningChartContextRequest $request,
        RentalUsageContextService $service,
    ): AnonymousResourceCollection {
        $resolved = $this->resolveRunningChart($request, $service);
        $agreementIds = collect($resolved['contexts'])
            ->map(fn (array $context): int => (int) $context['agreement']->getKey())
            ->values();

        return RentalUsageLogResource::collection(
            RentalUsageLog::query()
                ->where('tenant_id', $request->tenantId())
                ->where('organization_unit_id', $request->organizationUnitId())
                ->where('vehicle_id', $resolved['selected_allocation']->vehicle_id)
                ->whereDate('usage_date', (string) $request->input('usage_date'))
                ->whereHas('contexts', fn (Builder $query) => $query
                    ->whereIn('agreement_id', $agreementIds))
                ->with([
                    'vehicle.make',
                    'vehicle.model',
                    'driver',
                    'events',
                    'contexts.agreement.customer',
                    'contexts.agreement.supplier',
                    'contexts.rateSnapshot',
                ])
                ->orderBy('usage_date')
                ->orderBy('effective_at')
                ->orderBy('operational_sequence')
                ->orderBy('id')
                ->get()
                ->filter(function (RentalUsageLog $log) use ($agreementIds): bool {
                    $logAgreementIds = $log->contexts
                        ->pluck('agreement_id')
                        ->map(fn ($id): int => (int) $id)
                        ->all();

                    return $agreementIds->every(fn (int $id): bool => in_array($id, $logAgreementIds, true));
                })
                ->values(),
        );
    }

    public function storeTrip(
        StoreRunningChartTripRequest $request,
        RentalUsageLogService $usageLogs,
        RentalUsageEventService $events,
        VehicleRentalAuthorizationService $authorization,
    ): JsonResponse {
        $authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            VehicleRentalAuthorizationService::RECORD_USAGE,
        );
        $this->assertEventPermissions($request, $request->eventData(), $authorization);

        $log = DB::transaction(function () use ($request, $usageLogs, $events): RentalUsageLog {
            $agreement = $this->agreement($request, $request->selectedAgreementId());
            $counterpart = $request->counterpartAgreementId() === null
                ? null
                : $this->agreement($request, $request->counterpartAgreementId());
            $log = $usageLogs->createForMode(
                $request->mode(),
                $agreement,
                $request->toData(),
                $counterpart,
                $request->counterpartAgreementVehicleId(),
            );
            if ($log->wasRecentlyCreated) {
                foreach ($request->eventData() as $eventData) {
                    $events->create($log, $eventData);
                }
            }

            return $this->runningChartLog($request, (int) $log->getKey());
        });

        return (new RentalUsageLogResource($log))->response()->setStatusCode(201);
    }

    public function updateTrip(
        StoreRunningChartTripRequest $request,
        int $usageLog,
        RentalUsageLogService $usageLogs,
        RentalUsageEventService $events,
        VehicleRentalAuthorizationService $authorization,
    ): RentalUsageLogResource {
        $authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            VehicleRentalAuthorizationService::RECORD_USAGE,
        );
        $this->assertEventPermissions($request, $request->eventData(), $authorization);

        $log = DB::transaction(function () use ($request, $usageLog, $usageLogs, $events): RentalUsageLog {
            $agreement = $this->agreement($request, $request->selectedAgreementId());
            $counterpart = $request->counterpartAgreementId() === null
                ? null
                : $this->agreement($request, $request->counterpartAgreementId());
            $log = $usageLogs->updateForMode(
                $request->mode(),
                $this->runningChartLog($request, $usageLog),
                $request->toData(),
                $agreement,
                $counterpart,
                $request->counterpartAgreementVehicleId(),
            );
            foreach ($log->events()->lockForUpdate()->get() as $event) {
                $events->delete($event);
            }
            foreach ($request->eventData() as $eventData) {
                $events->create($log, $eventData);
            }

            return $this->runningChartLog($request, (int) $log->getKey());
        });

        return new RentalUsageLogResource($log);
    }

    public function destroyTrip(
        ListRentalRequest $request,
        int $usageLog,
        RentalUsageLogService $usageLogs,
        VehicleRentalAuthorizationService $authorization,
    ): JsonResponse {
        $authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            VehicleRentalAuthorizationService::RECORD_USAGE,
        );
        $usageLogs->delete($this->runningChartLog($request, $usageLog));

        return response()->json(null, 204);
    }

    public function submitTrip(
        RentalActionRequest $request,
        int $usageLog,
        RentalUsageLogService $usageLogs,
        VehicleRentalAuthorizationService $authorization,
    ): RentalUsageLogResource {
        $authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            VehicleRentalAuthorizationService::RECORD_USAGE,
        );

        return new RentalUsageLogResource($usageLogs->changeStatus(
            $this->runningChartLog($request, $usageLog),
            RentalUsageLogStatus::Submitted,
            $request->currentUserId(),
            $request->input('reason'),
        ));
    }

    public function submitDaily(
        SubmitRunningChartDailyRequest $request,
        RentalUsageLogService $usageLogs,
        RentalUsageEventService $events,
        VehicleRentalAuthorizationService $authorization,
    ): AnonymousResourceCollection {
        $authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            VehicleRentalAuthorizationService::RECORD_USAGE,
        );
        foreach ($request->tripRows() as $trip) {
            $this->assertEventPermissions($request, $request->eventData($trip), $authorization);
        }

        $logs = DB::transaction(function () use ($request, $usageLogs, $events): array {
            $agreement = $this->agreement($request, $request->selectedAgreementId());
            $counterpart = $request->counterpartAgreementId() === null
                ? null
                : $this->agreement($request, $request->counterpartAgreementId());
            $submitted = [];

            foreach ($request->tripRows() as $trip) {
                $eventData = $request->eventData($trip);
                $tripId = $request->tripId($trip);
                if ($tripId === null) {
                    $log = $usageLogs->createForMode(
                        $request->mode(),
                        $agreement,
                        $request->toData($trip),
                        $counterpart,
                        $request->counterpartAgreementVehicleId(),
                    );
                    if ($log->wasRecentlyCreated) {
                        foreach ($eventData as $event) {
                            $events->create($log, $event);
                        }
                    }
                } else {
                    $log = $usageLogs->updateForMode(
                        $request->mode(),
                        $this->runningChartLog($request, $tripId),
                        $request->toData($trip),
                        $agreement,
                        $counterpart,
                        $request->counterpartAgreementVehicleId(),
                    );
                    foreach ($log->events()->lockForUpdate()->get() as $event) {
                        $events->delete($event);
                    }
                    foreach ($eventData as $event) {
                        $events->create($log, $event);
                    }
                }

                $submitted[] = $usageLogs->changeStatus(
                    $log->refresh(),
                    RentalUsageLogStatus::Submitted,
                    $request->currentUserId(),
                );
            }

            return $submitted;
        });

        return RentalUsageLogResource::collection($logs);
    }

    public function approveTrip(
        RentalActionRequest $request,
        int $usageLog,
        RentalUsageLogService $usageLogs,
        VehicleRentalAuthorizationService $authorization,
    ): RentalUsageLogResource {
        $authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            VehicleRentalAuthorizationService::APPROVE_USAGE,
        );
        $mileageOverride = $request->boolean('mileage_override');
        if ($mileageOverride) {
            $authorization->assert(
                $request->currentUserId(),
                $request->tenantId(),
                VehicleRentalAuthorizationService::OVERRIDE_MILEAGE,
            );
            if (trim((string) $request->input('reason')) === '') {
                abort(422, 'A reason is required for a mileage-chain override.');
            }
        }

        return new RentalUsageLogResource($usageLogs->changeStatus(
            $this->runningChartLog($request, $usageLog),
            RentalUsageLogStatus::Approved,
            $request->currentUserId(),
            $request->input('reason'),
            $mileageOverride,
        ));
    }

    public function rejectTrip(
        RentalActionRequest $request,
        int $usageLog,
        RentalUsageLogService $usageLogs,
        VehicleRentalAuthorizationService $authorization,
    ): RentalUsageLogResource {
        $authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            VehicleRentalAuthorizationService::APPROVE_USAGE,
        );

        return new RentalUsageLogResource($usageLogs->changeStatus(
            $this->runningChartLog($request, $usageLog),
            RentalUsageLogStatus::Rejected,
            $request->currentUserId(),
            $request->input('reason'),
        ));
    }

    public function preview(
        RunningChartPreviewRequest $request,
        RentalUsageContextService $contexts,
        RentalChargeCalculationService $charges,
        VehicleRentalAuthorizationService $authorization,
    ): JsonResponse {
        $authorization->assert(
            $request->currentUserId(),
            $request->tenantId(),
            VehicleRentalAuthorizationService::RECORD_USAGE,
        );

        $agreement = $this->agreement($request, $request->selectedAgreementId());
        $allocation = $this->allocation($agreement, $request->selectedAgreementVehicleId());
        $counterpart = $request->counterpartAgreementId() === null
            ? null
            : $this->agreement($request, $request->counterpartAgreementId());
        $counterpartAllocation = $counterpart === null || $request->counterpartAgreementVehicleId() === null
            ? null
            : $this->allocation($counterpart, $request->counterpartAgreementVehicleId());
        $resolved = $contexts->resolveForMode(
            $request->mode(),
            $agreement,
            $allocation,
            (string) $request->input('usage_date'),
            null,
            $counterpart,
            $counterpartAllocation,
        );

        return response()->json(['data' => $charges->previewRunningChart($resolved, $request->trips())]);
    }

    private function resolveRunningChart(
        RunningChartContextRequest $request,
        RentalUsageContextService $service,
    ): array {
        $agreement = $this->agreement($request, $request->selectedAgreementId())
            ->load(['rateSnapshot', 'customer', 'supplier']);
        $allocation = $this->allocation($agreement, $request->selectedAgreementVehicleId());
        if ($request->mode() === null) {
            return $service->resolve(
                $agreement,
                $allocation,
                (string) $request->input('usage_date'),
                $request->filled('start_time') ? (string) $request->input('start_time') : null,
            );
        }

        $counterpart = $request->counterpartAgreementId() === null
            ? null
            : $this->agreement($request, $request->counterpartAgreementId())
                ->load(['rateSnapshot', 'customer', 'supplier']);
        $counterpartAllocation = $counterpart === null || $request->counterpartAgreementVehicleId() === null
            ? null
            : $this->allocation($counterpart, $request->counterpartAgreementVehicleId());

        return $service->resolveForMode(
            $request->mode(),
            $agreement,
            $allocation,
            (string) $request->input('usage_date'),
            $request->filled('start_time') ? (string) $request->input('start_time') : null,
            $counterpart,
            $counterpartAllocation,
        );
    }

    private function runningChartLog(TenantScopedRequest $request, int $id): RentalUsageLog
    {
        return RentalUsageLog::query()
            ->where('tenant_id', $request->tenantId())
            ->where('organization_unit_id', $request->organizationUnitId())
            ->whereKey($id)
            ->with([
                'vehicle.make',
                'vehicle.model',
                'driver',
                'events',
                'contexts.agreement.customer',
                'contexts.agreement.supplier',
                'contexts.rateSnapshot',
            ])
            ->firstOrFail();
    }

    /**
     * @param  list<\Modules\VehicleRental\DTOs\RentalUsageEventData>  $events
     */
    private function assertEventPermissions(
        TenantScopedRequest $request,
        array $events,
        VehicleRentalAuthorizationService $authorization,
    ): void {
        foreach ($events as $event) {
            if ($event->eventType !== RentalUsageEventType::Holiday) {
                continue;
            }
            $authorization->assert(
                $request->currentUserId(),
                $request->tenantId(),
                VehicleRentalAuthorizationService::CLASSIFY_HOLIDAY,
            );
            if (trim((string) $event->remarks) === '') {
                abort(422, 'Holiday usage classification requires a documented reason or calendar reference.');
            }
        }
    }
}
