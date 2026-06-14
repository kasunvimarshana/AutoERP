<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Http\Requests\ListRentalRequest;
use Modules\VehicleRental\Http\Requests\RentalActionRequest;
use Modules\VehicleRental\Http\Requests\StoreRentalAgreementRequest;
use Modules\VehicleRental\Http\Resources\RentalAgreementResource;
use Modules\VehicleRental\Http\Resources\RentalStatusHistoryResource;
use Modules\VehicleRental\Services\RentalAgreementService;
use Modules\VehicleRental\Services\RentalInvoiceIntegrationService;

final class RentalAgreementController extends RentalController
{
    public function index(ListRentalRequest $request, RentalAgreementService $service): AnonymousResourceCollection
    {
        return RentalAgreementResource::collection($service->paginate(
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->validated(),
            $request->perPage(),
        ));
    }

    public function store(StoreRentalAgreementRequest $request, RentalAgreementService $service): JsonResponse
    {
        return (new RentalAgreementResource($service->create($request->toData())))
            ->response()->setStatusCode(201);
    }

    public function show(
        ListRentalRequest $request,
        int $agreement,
        RentalAgreementService $service,
        RentalInvoiceIntegrationService $invoices,
    ): RentalAgreementResource {
        $model = $this->agreement($request, $agreement)->load($service->relations());
        $invoices->billableCharges($model);

        return new RentalAgreementResource($model->refresh()->load($service->relations()));
    }

    public function confirm(RentalActionRequest $request, int $agreement, RentalAgreementService $service): RentalAgreementResource
    {
        return $this->change($request, $agreement, RentalAgreementStatus::Confirmed, $service);
    }

    public function activate(RentalActionRequest $request, int $agreement, RentalAgreementService $service): RentalAgreementResource
    {
        return $this->change($request, $agreement, RentalAgreementStatus::Active, $service);
    }

    public function markReturned(RentalActionRequest $request, int $agreement, RentalAgreementService $service): RentalAgreementResource
    {
        return $this->change($request, $agreement, RentalAgreementStatus::Returned, $service);
    }

    public function complete(RentalActionRequest $request, int $agreement, RentalAgreementService $service): RentalAgreementResource
    {
        return $this->change($request, $agreement, RentalAgreementStatus::Completed, $service);
    }

    public function cancel(RentalActionRequest $request, int $agreement, RentalAgreementService $service): RentalAgreementResource
    {
        return $this->change($request, $agreement, RentalAgreementStatus::Cancelled, $service);
    }

    public function history(ListRentalRequest $request, int $agreement): AnonymousResourceCollection
    {
        return RentalStatusHistoryResource::collection(
            $this->agreement($request, $agreement)->statusHistories()->get(),
        );
    }

    private function change(
        RentalActionRequest $request,
        int $agreement,
        RentalAgreementStatus $status,
        RentalAgreementService $service,
    ): RentalAgreementResource {
        return new RentalAgreementResource($service->changeStatus(
            $this->agreement($request, $agreement),
            $status,
            $request->currentUserId(),
            $request->input('reason'),
        ));
    }
}
