<?php

declare(strict_types=1);

namespace Modules\Payments\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Payments\Application\Contracts\PaymentServiceInterface;
use Modules\Payments\Application\DTOs\CreatePaymentDTO;
use Modules\Payments\Domain\Entities\Payment;
use Modules\Payments\Domain\Exceptions\PaymentNotFoundException;
use Modules\Payments\Domain\ValueObjects\PaymentMethod;
use Modules\Payments\Infrastructure\Http\Requests\ChangePaymentStatusRequest;
use Modules\Payments\Infrastructure\Http\Requests\CreatePaymentRequest;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentServiceInterface $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $tenantId = (string) $request->header('X-Tenant-ID');
        $orgUnitId = (string) $request->query('org_unit_id', $tenantId);
        $payments = $this->service->listByTenant($tenantId, $orgUnitId);
        $data = array_map(fn (Payment $payment): array => $this->transform($payment), $payments);

        return response()->json(['data' => $data]);
    }

    public function byInvoice(Request $request, string $invoiceId): JsonResponse
    {
        $tenantId = (string) $request->header('X-Tenant-ID');
        $payments = $this->service->listByInvoice($tenantId, $invoiceId);
        $data = array_map(fn (Payment $payment): array => $this->transform($payment), $payments);

        return response()->json(['data' => $data]);
    }

    public function store(CreatePaymentRequest $request): JsonResponse
    {
        $tenantId = (string) $request->header('X-Tenant-ID');
        $validated = $request->validated();

        $dto = new CreatePaymentDTO(
            tenantId: $tenantId,
            orgUnitId: (string) ($validated['org_unit_id'] ?? $tenantId),
            paymentNumber: $validated['payment_number'],
            invoiceId: $validated['invoice_id'],
            paymentMethod: PaymentMethod::from($validated['payment_method']),
            amount: number_format((float) $validated['amount'], 6, '.', ''),
            currency: strtoupper((string) ($validated['currency'] ?? 'USD')),
            referenceNumber: $validated['reference_number'] ?? null,
            notes: $validated['notes'] ?? null,
            metadata: $validated['metadata'] ?? null,
        );

        $payment = $this->service->create($dto);

        return response()->json(['data' => $this->transform($payment)], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = (string) $request->header('X-Tenant-ID');

        try {
            $payment = $this->service->getById($tenantId, $id);

            return response()->json(['data' => $this->transform($payment)]);
        } catch (PaymentNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }

    public function changeStatus(ChangePaymentStatusRequest $request, string $id): JsonResponse
    {
        $tenantId = (string) $request->header('X-Tenant-ID');

        try {
            $payment = $this->service->updateStatus($tenantId, $id, (string) $request->validated('status'));

            return response()->json(['data' => $this->transform($payment)]);
        } catch (PaymentNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenantId = (string) $request->header('X-Tenant-ID');

        try {
            $this->service->delete($tenantId, $id);

            return response()->json(null, 204);
        } catch (PaymentNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }

    private function transform(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'tenant_id' => $payment->tenantId,
            'org_unit_id' => $payment->orgUnitId,
            'row_version' => $payment->rowVersion,
            'payment_number' => $payment->paymentNumber,
            'invoice_id' => $payment->invoiceId,
            'payment_method' => $payment->paymentMethod->value,
            'status' => $payment->status->value,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'paid_at' => $payment->paidAt?->format('Y-m-d H:i:s'),
            'reference_number' => $payment->referenceNumber,
            'notes' => $payment->notes,
            'metadata' => $payment->metadata,
            'is_active' => $payment->isActive,
            'created_at' => $payment->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $payment->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
