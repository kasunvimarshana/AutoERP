<?php

declare(strict_types=1);

namespace Modules\Receipts\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Receipts\Application\Contracts\ReceiptServiceInterface;
use Modules\Receipts\Application\DTOs\CreateReceiptDTO;
use Modules\Receipts\Domain\Entities\Receipt;
use Modules\Receipts\Domain\Exceptions\ReceiptNotFoundException;
use Modules\Receipts\Domain\ValueObjects\ReceiptType;
use Modules\Receipts\Infrastructure\Http\Requests\ChangeReceiptStatusRequest;
use Modules\Receipts\Infrastructure\Http\Requests\CreateReceiptRequest;

class ReceiptController extends Controller
{
    public function __construct(private readonly ReceiptServiceInterface $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $tenantId = (string) $request->header('X-Tenant-ID');
        $orgUnitId = (string) $request->query('org_unit_id', $tenantId);

        $receipts = $this->service->listByTenant($tenantId, $orgUnitId);

        return response()->json(['data' => array_map(fn (Receipt $receipt): array => $this->transform($receipt), $receipts)]);
    }

    public function byPayment(Request $request, string $paymentId): JsonResponse
    {
        $tenantId = (string) $request->header('X-Tenant-ID');

        $receipts = $this->service->listByPayment($tenantId, $paymentId);

        return response()->json(['data' => array_map(fn (Receipt $receipt): array => $this->transform($receipt), $receipts)]);
    }

    public function store(CreateReceiptRequest $request): JsonResponse
    {
        $tenantId = (string) $request->header('X-Tenant-ID');
        $validated = $request->validated();

        $dto = new CreateReceiptDTO(
            tenantId: $tenantId,
            orgUnitId: (string) ($validated['org_unit_id'] ?? $tenantId),
            receiptNumber: $validated['receipt_number'],
            paymentId: $validated['payment_id'],
            invoiceId: $validated['invoice_id'] ?? null,
            receiptType: ReceiptType::from($validated['receipt_type']),
            amount: number_format((float) $validated['amount'], 6, '.', ''),
            currency: strtoupper((string) ($validated['currency'] ?? 'USD')),
            notes: $validated['notes'] ?? null,
            metadata: $validated['metadata'] ?? null,
        );

        $receipt = $this->service->create($dto);

        return response()->json(['data' => $this->transform($receipt)], 201);
    }

    public function show(string $id): JsonResponse
    {
        try {
            $receipt = $this->service->getById($id);

            return response()->json(['data' => $this->transform($receipt)]);
        } catch (ReceiptNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }

    public function changeStatus(ChangeReceiptStatusRequest $request, string $id): JsonResponse
    {
        try {
            $receipt = $this->service->updateStatus($id, (string) $request->validated('status'));

            return response()->json(['data' => $this->transform($receipt)]);
        } catch (ReceiptNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $this->service->delete($id);

            return response()->json(null, 204);
        } catch (ReceiptNotFoundException $exception) {
            return response()->json(['message' => $exception->getMessage()], 404);
        }
    }

    private function transform(Receipt $receipt): array
    {
        return [
            'id' => $receipt->id,
            'tenant_id' => $receipt->tenantId,
            'org_unit_id' => $receipt->orgUnitId,
            'row_version' => $receipt->rowVersion,
            'receipt_number' => $receipt->receiptNumber,
            'payment_id' => $receipt->paymentId,
            'invoice_id' => $receipt->invoiceId,
            'receipt_type' => $receipt->receiptType->value,
            'status' => $receipt->status->value,
            'amount' => $receipt->amount,
            'currency' => $receipt->currency,
            'issued_at' => $receipt->issuedAt?->format('Y-m-d H:i:s'),
            'notes' => $receipt->notes,
            'metadata' => $receipt->metadata,
            'is_active' => $receipt->isActive,
            'created_at' => $receipt->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $receipt->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
