<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Modules\VehicleRental\Http\Requests\DeleteRentalAssignmentRequest;
use Modules\VehicleRental\Http\Requests\ListRentalRequest;
use Modules\VehicleRental\Http\Requests\RentalActionRequest;
use Modules\VehicleRental\Http\Requests\ReplaceRentalAssignmentRequest;
use Modules\VehicleRental\Http\Requests\StoreRentalAssignmentRequest;
use Modules\VehicleRental\Http\Requests\StoreRentalCustodyRequest;
use Modules\VehicleRental\Http\Requests\UpdateRentalAssignmentRequest;
use Modules\VehicleRental\Http\Resources\RentalAssignmentResource;
use Modules\VehicleRental\Models\RentalAssignment;
use Modules\VehicleRental\Services\RentalAssignmentService;
use Modules\VehicleRental\Services\RentalCustodyService;
use Modules\VehicleRental\Services\RentalReplacementService;

final class RentalAssignmentController extends RentalController
{
    public function index(ListRentalRequest $request, RentalAssignmentService $service): AnonymousResourceCollection
    {
        $query = RentalAssignment::query()
            ->forContext($request->tenantId(), $request->organizationUnitId())
            ->with($service->relations())
            ->orderByDesc('starts_at');
        if ($request->filled('agreement_id')) {
            $query->where('agreement_id', $request->validated('agreement_id'));
        }
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->validated('vehicle_id'));
        }
        if ($request->filled('assignment_side')) {
            $query->where('side', $request->validated('assignment_side'));
        }
        if ($request->filled('assignment_status')) {
            $query->where('status', $request->validated('assignment_status'));
        }
        if ($request->filled('search')) {
            $search = trim((string) $request->validated('search'));
            $query->where(function (Builder $scope) use ($search): void {
                $scope->whereHas('agreement', fn (Builder $agreement): Builder => $agreement
                    ->where('agreement_number', 'like', "%{$search}%"))
                    ->orWhereHas('vehicle', fn (Builder $vehicle): Builder => $vehicle
                        ->where('vehicle_number', 'like', "%{$search}%")
                        ->orWhere('registration_number', 'like', "%{$search}%"));
            });
        }

        return RentalAssignmentResource::collection($query->paginate($request->perPage()));
    }

    public function store(StoreRentalAssignmentRequest $request, RentalAssignmentService $service): JsonResponse
    {
        return (new RentalAssignmentResource($service->create($request->toData())))
            ->response()->setStatusCode(201);
    }

    public function show(ListRentalRequest $request, int $assignment, RentalAssignmentService $service): RentalAssignmentResource
    {
        return new RentalAssignmentResource($this->assignment($request, $assignment)->load($service->relations()));
    }

    public function update(
        UpdateRentalAssignmentRequest $request,
        int $assignment,
        RentalAssignmentService $service,
    ): RentalAssignmentResource {
        return new RentalAssignmentResource($service->update(
            $this->assignment($request, $assignment),
            $request->toData(),
            $request->expectedVersion(),
        ));
    }

    public function destroy(
        DeleteRentalAssignmentRequest $request,
        int $assignment,
        RentalAssignmentService $service,
    ): Response {
        $service->deletePlanned(
            $this->assignment($request, $assignment),
            $request->expectedVersion(),
        );

        return response()->noContent();
    }

    public function custody(StoreRentalCustodyRequest $request, int $assignment, RentalCustodyService $service): RentalAssignmentResource
    {
        return new RentalAssignmentResource($service->record(
            $this->assignment($request, $assignment),
            $request->toData(),
            $request->expectedVersion(),
        ));
    }

    public function cancel(RentalActionRequest $request, int $assignment, RentalAssignmentService $service): RentalAssignmentResource
    {
        return new RentalAssignmentResource($service->cancel(
            $this->assignment($request, $assignment),
            $request->expectedVersion(),
            $request->currentUserId(),
        ));
    }

    public function replace(
        ReplaceRentalAssignmentRequest $request,
        int $assignment,
        RentalReplacementService $service,
    ): RentalAssignmentResource {
        return new RentalAssignmentResource($service->replace(
            $this->assignment($request, $assignment),
            $request->toData(),
            $request->expectedVersion(),
        ));
    }
}
