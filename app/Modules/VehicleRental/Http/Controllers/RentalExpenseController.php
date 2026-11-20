<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Modules\Invoice\Http\Resources\InvoiceResource;
use Modules\VehicleRental\Enums\AgreementKind;
use Modules\VehicleRental\Enums\RentalExpenseStatus;
use Modules\VehicleRental\Http\Requests\RentalExpenseBillAllocationRequest;
use Modules\VehicleRental\Http\Requests\RentalExpenseQueryRequest;
use Modules\VehicleRental\Http\Requests\RentalExpenseTransitionRequest;
use Modules\VehicleRental\Http\Requests\StoreRentalExpenseRequest;
use Modules\VehicleRental\Http\Resources\RentalExpenseResource;
use Modules\VehicleRental\Models\RentalExpense;
use Modules\VehicleRental\Models\RentalExpenseAllocation;
use Modules\VehicleRental\Services\RentalAuthorization;
use Modules\VehicleRental\Services\RentalExpenseService;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

final class RentalExpenseController extends Controller
{
    public function __construct(
        private readonly RentalExpenseService $service,
        private readonly RentalAuthorization $auth,
    ) {}

    public function index(RentalExpenseQueryRequest $request): AnonymousResourceCollection
    {
        $context = $request->context();
        $this->auth->assertExpense($context, write: false);

        $expenses = $this->service->paginate(
            $context->tenantId,
            $context->organizationUnitId,
            $request->query(),
            (int) $request->query('per_page', 15),
        );

        return RentalExpenseResource::collection($expenses);
    }

    public function store(StoreRentalExpenseRequest $request): JsonResponse
    {
        $context = $request->context();
        $this->auth->assertExpense($context, write: true);

        $expense = $this->service->create(
            $request->validated(),
            $context->tenantId,
            $context->organizationUnitId,
            $context->actorId,
        );

        return (new RentalExpenseResource($expense))->response()->setStatusCode(201);
    }

    public function show(RentalExpenseQueryRequest $request, RentalExpense $expense): RentalExpenseResource
    {
        $context = $request->context();
        $this->auth->assertExpense($context, write: false);

        $expense = RentalExpense::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->with($this->service->relations())
            ->findOrFail($expense->getKey());

        return new RentalExpenseResource($expense);
    }

    public function transition(RentalExpenseTransitionRequest $request, RentalExpense $expense): RentalExpenseResource
    {
        $context = $request->context();
        $this->auth->assertExpense($context, write: true);

        $expense = RentalExpense::query()
            ->forContext($context->tenantId, $context->organizationUnitId)
            ->findOrFail($expense->getKey());

        $updated = $this->service->transition(
            $expense,
            RentalExpenseStatus::from((string) $request->validated('status')),
            $request->expectedVersion(),
            $context->actorId,
            (array) $request->validated('allocations', []),
        );

        return new RentalExpenseResource($updated);
    }

    public function billAllocation(RentalExpenseBillAllocationRequest $request, int $allocation): JsonResponse
    {
        $context = $request->context();
        $this->auth->assertBilling($context, AgreementKind::Customer);

        $alloc = RentalExpenseAllocation::query()
            ->forTenant($context->tenantId)
            ->findOrFail($allocation);

        $invoice = $this->service->billAllocation($alloc, $context, $request->validated());

        return (new InvoiceResource($invoice))->response()->setStatusCode(201);
    }

    public function unlinkAllocationInvoice(RentalExpenseQueryRequest $request, int $allocation): JsonResponse
    {
        $context = $request->context();
        $this->auth->assertBilling($context, AgreementKind::Customer);

        $alloc = RentalExpenseAllocation::query()
            ->forTenant($context->tenantId)
            ->where('organization_unit_id', $context->organizationUnitId)
            ->findOrFail($allocation);

        $updated = $this->service->unlinkCancelledInvoice($alloc, $context);
        $updated->load(['invoice', 'customer', 'supplier', 'targetCustomerAgreement', 'targetOwnerAgreement']);

        $payload = [
            'id' => $updated->id,
            'row_version' => $updated->row_version,
            'sequence' => $updated->sequence,
            'allocation_type' => $updated->allocation_type->value,
            'allocation_type_label' => $updated->allocation_type->label(),
            'net_amount' => (string) $updated->net_amount,
            'tax_amount' => (string) $updated->tax_amount,
            'total_amount' => (string) $updated->total_amount,
            'status' => $updated->status->value,
            'invoice_id' => $updated->invoice_id,
            'invoice' => $updated->invoice ? [
                'id' => $updated->invoice->id,
                'invoice_number' => $updated->invoice->invoice_number,
                'status' => (string) $updated->invoice->status,
            ] : null,
            'customer' => $updated->customer ? ['id' => $updated->customer->id, 'name' => $updated->customer->name] : null,
            'supplier' => $updated->supplier ? ['id' => $updated->supplier->id, 'name' => $updated->supplier->name] : null,
        ];

        return response()->json(array_merge($payload, ['data' => $payload]));
    }

    public function destroy(): JsonResponse
    {
        throw new MethodNotAllowedHttpException([], 'Rental expenses cannot be deleted to preserve financial audit history. Reject or reverse the expense instead.');
    }
}
