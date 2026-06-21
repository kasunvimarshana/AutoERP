<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Invoice\Enums\InvoiceStatus;
use Modules\VehicleRental\Http\Controllers\Concerns\ScopesVehicleRentalRequests;
use Modules\VehicleRental\Http\Requests\CreateVehicleFinancePayableRequest;
use Modules\VehicleRental\Http\Requests\ListRentalRequest;
use Modules\VehicleRental\Http\Requests\StoreVehicleFinanceAgreementRequest;
use Modules\VehicleRental\Http\Resources\VehicleFinanceAgreementResource;
use Modules\VehicleRental\Models\VehicleFinanceAgreement;
use Modules\VehicleRental\Models\VehicleFinanceInstallment;
use Modules\VehicleRental\Services\VehicleFinanceService;
use Modules\VehicleRental\Services\VehicleRentalAuthorizationService;

final class VehicleFinanceController
{
    use ScopesVehicleRentalRequests;

    public function __construct(private readonly VehicleRentalAuthorizationService $authorization) {}

    public function index(ListRentalRequest $request, VehicleFinanceService $service): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::VIEW_FINANCIAL);
        return VehicleFinanceAgreementResource::collection($service->paginate($request->tenantId(), $request->organizationUnitId(), $request->validated(), $request->perPage()));
    }

    public function store(StoreVehicleFinanceAgreementRequest $request, VehicleFinanceService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::MANAGE_FINANCE_AGREEMENTS);
        return (new VehicleFinanceAgreementResource($service->create($request->validated(), $request->tenantId(), $request->organizationUnitId(), $request->currentUserId())))->response()->setStatusCode(201);
    }

    public function show(ListRentalRequest $request, int $agreement, VehicleFinanceService $service): VehicleFinanceAgreementResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::VIEW_FINANCIAL);
        return new VehicleFinanceAgreementResource($this->scope(VehicleFinanceAgreement::query(), $request)->with($service->relations())->findOrFail($agreement));
    }

    public function activate(ListRentalRequest $request, int $agreement, VehicleFinanceService $service): VehicleFinanceAgreementResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::MANAGE_FINANCE_AGREEMENTS);
        return new VehicleFinanceAgreementResource($service->activate($this->scope(VehicleFinanceAgreement::query(), $request)->findOrFail($agreement), $request->currentUserId()));
    }

    public function createPayable(CreateVehicleFinancePayableRequest $request, int $installment, VehicleFinanceService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::CREATE_FINANCIAL_DOCUMENTS);
        $model = $this->scope(VehicleFinanceInstallment::query(), $request)->findOrFail($installment);
        $invoice = $service->createInstallmentPayable($model, (string) $request->input('invoice_date'), InvoiceStatus::from((string) $request->input('status')), $request->currentUserId());
        return response()->json(['data' => ['id' => (int) $invoice->getKey(), 'invoice_number' => $invoice->invoice_number, 'status' => $invoice->status->value]], 201);
    }

    public function refreshDueStatuses(ListRentalRequest $request, VehicleFinanceService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::MANAGE_FINANCE_AGREEMENTS);
        return response()->json(['data' => ['updated' => $service->refreshDueStatuses(
            $request->tenantId(),
            $request->organizationUnitId(),
            $request->input('date_to'),
        )]]);
    }
}
