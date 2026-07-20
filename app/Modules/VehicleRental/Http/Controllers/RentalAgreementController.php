<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Modules\VehicleRental\Http\Requests\DeleteRentalAgreementRequest;
use Modules\VehicleRental\Http\Requests\ListRentalRequest;
use Modules\VehicleRental\Http\Requests\RentalActionRequest;
use Modules\VehicleRental\Http\Requests\StoreRentalAgreementRequest;
use Modules\VehicleRental\Http\Requests\StoreRentalRateVersionRequest;
use Modules\VehicleRental\Http\Requests\UpdateRentalAgreementRequest;
use Modules\VehicleRental\Http\Resources\RentalAgreementResource;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Services\RentalAgreementService;

final class RentalAgreementController extends RentalController
{
    public function index(ListRentalRequest $request, RentalAgreementService $service): AnonymousResourceCollection
    {
        $query = RentalAgreement::query()
            ->forContext($request->tenantId(), $request->organizationUnitId())
            ->with(['customer', 'supplier', 'currency', 'taxGroup', 'rateVersions.lines'])
            ->orderByDesc('starts_on')
            ->orderByDesc('id');
        if ($request->filled('kind')) {
            $query->where('kind', $request->validated('kind'));
        }
        if ($request->filled('agreement_status')) {
            $query->where('status', $request->validated('agreement_status'));
        }
        if ($request->filled('search')) {
            $search = trim((string) $request->validated('search'));
            $query->where(function (Builder $scope) use ($search): void {
                $scope->where('agreement_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn (Builder $party) => $party->where('display_name', 'like', "%{$search}%"))
                    ->orWhereHas('supplier', fn (Builder $party) => $party->where('display_name', 'like', "%{$search}%"));
            });
        }

        return RentalAgreementResource::collection($query->paginate($request->perPage()));
    }

    public function store(StoreRentalAgreementRequest $request, RentalAgreementService $service): JsonResponse
    {
        return (new RentalAgreementResource($service->create($request->toData())))
            ->response()->setStatusCode(201);
    }

    public function show(ListRentalRequest $request, int $agreement, RentalAgreementService $service): RentalAgreementResource
    {
        return new RentalAgreementResource($this->agreement($request, $agreement)->load($service->relations()));
    }

    public function update(UpdateRentalAgreementRequest $request, int $agreement, RentalAgreementService $service): RentalAgreementResource
    {
        return new RentalAgreementResource($service->update(
            $this->agreement($request, $agreement),
            $request->toData(),
            $request->expectedVersion(),
        ));
    }

    public function destroy(
        DeleteRentalAgreementRequest $request,
        int $agreement,
        RentalAgreementService $service,
    ): Response {
        $service->deleteDraft(
            $this->agreement($request, $agreement),
            $request->expectedVersion(),
        );

        return response()->noContent();
    }

    public function storeRateVersion(
        StoreRentalRateVersionRequest $request,
        int $agreement,
        RentalAgreementService $service,
    ): RentalAgreementResource {
        return new RentalAgreementResource($service->createSuccessorRateVersion(
            $this->agreement($request, $agreement),
            $request->toData(),
            $request->expectedVersion(),
        ));
    }

    public function activate(RentalActionRequest $request, int $agreement, RentalAgreementService $service): RentalAgreementResource
    {
        return new RentalAgreementResource($service->activate(
            $this->agreement($request, $agreement),
            $request->expectedVersion(),
            $request->currentUserId(),
        ));
    }

    public function close(RentalActionRequest $request, int $agreement, RentalAgreementService $service): RentalAgreementResource
    {
        return new RentalAgreementResource($service->close(
            $this->agreement($request, $agreement),
            $request->expectedVersion(),
            $request->currentUserId(),
        ));
    }
}
