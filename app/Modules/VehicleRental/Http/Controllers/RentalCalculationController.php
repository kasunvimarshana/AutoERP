<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\VehicleRental\Enums\RentalCalculationStatus;
use Modules\VehicleRental\Enums\RentalFinancialSide;
use Modules\VehicleRental\Http\Controllers\Concerns\ScopesVehicleRentalRequests;
use Modules\VehicleRental\Http\Requests\CalculateRentalRequest;
use Modules\VehicleRental\Http\Requests\CreateRentalInvoiceRequest;
use Modules\VehicleRental\Http\Requests\ListRentalRequest;
use Modules\VehicleRental\Http\Requests\RentalTransitionRequest;
use Modules\VehicleRental\Http\Resources\RentalCalculationRunResource;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalCalculationRun;
use Modules\VehicleRental\Services\RentalCalculationService;
use Modules\VehicleRental\Services\RentalInvoiceIntegrationService;
use Modules\VehicleRental\Services\VehicleRentalAuthorizationService;

final class RentalCalculationController
{
    use ScopesVehicleRentalRequests;

    public function __construct(private readonly VehicleRentalAuthorizationService $authorization) {}

    public function index(ListRentalRequest $request, RentalCalculationService $service): AnonymousResourceCollection
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::VIEW_FINANCIAL);
        return RentalCalculationRunResource::collection($service->paginate($request->tenantId(), $request->organizationUnitId(), $request->validated(), $request->perPage()));
    }

    public function calculate(CalculateRentalRequest $request, int $agreement, RentalCalculationService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::CALCULATE);
        $agreementModel = $this->scope(RentalAgreement::query(), $request)->findOrFail($agreement);
        return (new RentalCalculationRunResource($service->calculate(
            $agreementModel, RentalFinancialSide::from((string) $request->input('financial_side')),
            (string) $request->input('period_start'), (string) $request->input('period_end'), $request->currentUserId(),
        )))->response()->setStatusCode(201);
    }

    public function show(ListRentalRequest $request, int $run, RentalCalculationService $service): RentalCalculationRunResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::VIEW_FINANCIAL);
        return new RentalCalculationRunResource($this->scope(RentalCalculationRun::query(), $request)->with($service->relations())->findOrFail($run));
    }

    public function transition(RentalTransitionRequest $request, int $run, RentalCalculationService $service): RentalCalculationRunResource
    {
        $status = RentalCalculationStatus::from((string) $request->input('status'));
        $permission = in_array($status, [RentalCalculationStatus::Approved, RentalCalculationStatus::Reversed], true)
            ? VehicleRentalAuthorizationService::APPROVE_CALCULATIONS
            : VehicleRentalAuthorizationService::CALCULATE;
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), $permission);
        return new RentalCalculationRunResource($service->transition(
            $this->scope(RentalCalculationRun::query(), $request)->findOrFail($run),
            $status, $request->currentUserId(), $request->input('reason'),
        ));
    }

    public function createInvoice(CreateRentalInvoiceRequest $request, int $run, RentalInvoiceIntegrationService $service): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), VehicleRentalAuthorizationService::CREATE_FINANCIAL_DOCUMENTS);
        $model = $this->scope(RentalCalculationRun::query(), $request)->findOrFail($run);
        $invoice = $service->create(
            $model, (string) $request->input('invoice_date'), $request->input('due_date'),
            \Modules\Invoice\Enums\InvoiceStatus::from((string) $request->input('status')),
            $request->input('line_ids'), $request->currentUserId(), $request->input('notes'),
        );
        return response()->json(['data' => ['id' => (int) $invoice->getKey(), 'invoice_number' => $invoice->invoice_number, 'status' => $invoice->status->value]], 201);
    }
}
