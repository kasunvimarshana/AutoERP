<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Modules\Invoice\Http\Resources\InvoiceResource;
use Modules\VehicleRental\Http\Requests\ActivateVehicleFinanceAgreementRequest;
use Modules\VehicleRental\Http\Requests\CancelVehicleFinanceAgreementRequest;
use Modules\VehicleRental\Http\Requests\CreateVehicleFinancePayableRequest;
use Modules\VehicleRental\Http\Requests\RefreshVehicleFinanceDueStatusesRequest;
use Modules\VehicleRental\Http\Requests\StoreVehicleFinanceAgreementRequest;
use Modules\VehicleRental\Http\Requests\VehicleFinanceQueryRequest;
use Modules\VehicleRental\Http\Resources\VehicleFinanceAgreementResource;
use Modules\VehicleRental\Models\VehicleFinanceAgreement;
use Modules\VehicleRental\Models\VehicleFinanceInstallment;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\VehicleFinanceService;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

final class VehicleFinanceController extends Controller
{
    public function __construct(
        private readonly VehicleFinanceService $service,
        private readonly RentalAuthorization $auth,
    ) {}

    public function index(VehicleFinanceQueryRequest $request): AnonymousResourceCollection
    {
        $context = $request->context();
        $this->auth->assertFinance($context, write: false);

        $agreements = $this->service->paginate(
            $context->tenantId,
            $context->organizationUnitId,
            $request->query(),
            (int) $request->query('per_page', 15),
        );

        return VehicleFinanceAgreementResource::collection($agreements);
    }

    public function store(StoreVehicleFinanceAgreementRequest $request): JsonResponse
    {
        $context = $request->context();
        $this->auth->assertFinance($context, write: true);

        $agreement = $this->service->create(
            $request->validated(),
            $context->tenantId,
            $context->organizationUnitId,
            $context->actorId,
        );

        return (new VehicleFinanceAgreementResource($agreement))->response()->setStatusCode(201);
    }

    public function show(VehicleFinanceQueryRequest $request, VehicleFinanceAgreement $agreement): VehicleFinanceAgreementResource
    {
        $context = $request->context();
        $this->auth->assertFinance($context, write: false);

        $agreement = VehicleFinanceAgreement::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->with($this->service->relations())
            ->findOrFail($agreement->getKey());

        return new VehicleFinanceAgreementResource($agreement);
    }

    public function activate(ActivateVehicleFinanceAgreementRequest $request, VehicleFinanceAgreement $agreement): VehicleFinanceAgreementResource
    {
        $context = $request->context();
        $this->auth->assertFinance($context, write: true);

        $agreement = VehicleFinanceAgreement::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->findOrFail($agreement->getKey());

        $activated = $this->service->activate(
            $agreement,
            $request->expectedVersion(),
            $context->actorId,
        );

        return new VehicleFinanceAgreementResource($activated);
    }

    public function cancel(CancelVehicleFinanceAgreementRequest $request, VehicleFinanceAgreement $agreement): VehicleFinanceAgreementResource
    {
        $context = $request->context();
        $this->auth->assertFinance($context, write: true);

        $agreement = VehicleFinanceAgreement::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->findOrFail($agreement->getKey());

        $cancelled = $this->service->cancel(
            $agreement,
            $request->expectedVersion(),
            $context->actorId,
            $request->validated('reason'),
        );

        return new VehicleFinanceAgreementResource($cancelled);
    }

    public function createPayable(CreateVehicleFinancePayableRequest $request, VehicleFinanceInstallment $installment): JsonResponse
    {
        $context = $request->context();
        $this->auth->assertFinance($context, write: true);

        $installment = VehicleFinanceInstallment::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->with('financeAgreement')
            ->findOrFail($installment->getKey());

        $invoice = $this->service->createPayableInvoice(
            $installment,
            (string) $request->validated('invoice_date'),
            $request->expectedVersion(),
            $context->actorId,
        );

        return (new InvoiceResource($invoice))->response()->setStatusCode(201);
    }

    public function refreshDueStatuses(RefreshVehicleFinanceDueStatusesRequest $request): JsonResponse
    {
        $context = $request->context();
        $this->auth->assertFinance($context, write: true);

        $count = $this->service->refreshDueStatuses(
            $context->tenantId,
            $context->organizationUnitId,
            $request->validated('as_at'),
        );

        return response()->json(['updated' => $count]);
    }

    public function destroy(): JsonResponse
    {
        throw new MethodNotAllowedHttpException([], 'Vehicle finance agreements cannot be deleted to preserve financial audit history. Cancel the agreement instead.');
    }
}
