<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Core\Application\Results\Result;
use Modules\Payment\Application\Contracts\UseCases\PaymentAllocations\CreatePaymentAllocationServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\PaymentAllocations\ListPaymentAllocationsServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\Payments\CreatePaymentServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\Payments\DeletePaymentServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\Payments\GetPaymentServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\Payments\ListPaymentsServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\Payments\UpdatePaymentServiceInterface;
use Modules\Payment\Application\Contracts\UseCases\WriteOffs\CreateWriteOffServiceInterface;
use Modules\Purchase\Application\Contracts\Services\PurchaseIntegrationServiceInterface;

final class PurchasePaymentController extends Controller
{
    public function __construct(
        private readonly PurchaseIntegrationServiceInterface $integration,
        private readonly ListPaymentsServiceInterface $listPayments,
        private readonly GetPaymentServiceInterface $getPayment,
        private readonly CreatePaymentServiceInterface $createPayment,
        private readonly UpdatePaymentServiceInterface $updatePayment,
        private readonly DeletePaymentServiceInterface $deletePayment,
        private readonly ListPaymentAllocationsServiceInterface $listAllocations,
        private readonly CreatePaymentAllocationServiceInterface $createAllocation,
        private readonly CreateWriteOffServiceInterface $createWriteOff,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $payload = $this->withContext($request);
        $perPage = isset($payload['per_page']) ? (int) $payload['per_page'] : 0;
        $page = isset($payload['page']) ? (int) $payload['page'] : 0;

        unset($payload['per_page'], $payload['page']);
        $payload['party_type'] = 'supplier';

        $result = $this->listPayments->execute($payload, $perPage, $page);
        if ($result->isFailure()) {
            return $this->respond($result);
        }

        $value = $result->valueOrFail();
        if (! $value instanceof PagedResult) {
            return response()->json(['message' => 'Unexpected list response.'], 500);
        }

        return response()->json([
            'data' => array_map(static fn ($row): array => $row->toArray(), $value->items),
            'meta' => [
                'total' => $value->total,
                'page' => $value->page,
                'per_page' => $value->perPage,
                'page_count' => $value->pageCount(),
                'has_more' => $value->hasMore(),
            ],
        ]);
    }

    public function show(int|string $id): JsonResponse
    {
        return $this->respond($this->getPayment->execute($id));
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->withContext($request);

        $sourceType = (string) ($payload['source_type'] ?? '');
        $sourceId = (int) ($payload['source_id'] ?? 0);
        if ($sourceType !== '' && $sourceId > 0) {
            return $this->respond($this->integration->createSourcePayment($sourceType, $sourceId, $payload));
        }

        return $this->respond($this->createPayment->execute($payload));
    }

    public function update(Request $request, int|string $id): JsonResponse
    {
        return $this->respond($this->updatePayment->execute($id, $this->withContext($request)));
    }

    public function destroy(int|string $id): JsonResponse
    {
        $result = $this->deletePayment->execute($id);
        if ($result->isFailure()) {
            return $this->respond($result);
        }

        return response()->json(null, 204);
    }

    public function post(Request $request, int|string $id): JsonResponse
    {
        return $this->respond($this->integration->postPayment($id, $this->withContext($request)));
    }

    public function void(Request $request, int|string $id): JsonResponse
    {
        $payload = $this->withContext($request);
        $payload['target_status'] = 'voided';

        return $this->respond($this->updatePayment->execute($id, ['status' => 'voided']));
    }

    public function reverse(Request $request, int|string $id): JsonResponse
    {
        return $this->respond($this->integration->reversePayment($id, $this->withContext($request)));
    }

    public function allocate(Request $request, int|string $id): JsonResponse
    {
        $payload = $this->withContext($request);
        $sourceType = (string) ($payload['source_type'] ?? '');
        $sourceId = (int) ($payload['source_id'] ?? 0);
        if ($sourceType !== '' && $sourceId > 0) {
            $payload['payment_id'] = (int) $id;
            return $this->respond($this->integration->allocateSourcePayment($sourceType, $sourceId, $payload));
        }

        $payload['payment_id'] = (int) $id;
        return $this->respond($this->createAllocation->execute($payload));
    }

    public function allocations(Request $request, int|string $id): JsonResponse
    {
        $payload = $this->withContext($request);
        $payload['payment_id'] = (int) $id;
        $perPage = isset($payload['per_page']) ? (int) $payload['per_page'] : 0;
        $page = isset($payload['page']) ? (int) $payload['page'] : 0;
        unset($payload['per_page'], $payload['page']);

        $result = $this->listAllocations->execute($payload, $perPage, $page);
        if ($result->isFailure()) {
            return $this->respond($result);
        }

        $value = $result->valueOrFail();
        if (! $value instanceof PagedResult) {
            return response()->json(['message' => 'Unexpected list response.'], 500);
        }

        return response()->json([
            'data' => array_map(static fn ($row): array => $row->toArray(), $value->items),
            'meta' => [
                'total' => $value->total,
                'page' => $value->page,
                'per_page' => $value->perPage,
                'page_count' => $value->pageCount(),
                'has_more' => $value->hasMore(),
            ],
        ]);
    }

    public function createAdvance(Request $request): JsonResponse
    {
        $payload = $this->withContext($request);
        $sourceType = (string) ($payload['source_type'] ?? '');
        $sourceId = (int) ($payload['source_id'] ?? 0);
        if ($sourceType === '' || $sourceId < 1) {
            return response()->json(['message' => 'source_type and source_id are required.'], 422);
        }

        return $this->respond($this->integration->createSourceAdvance($sourceType, $sourceId, $payload));
    }

    public function allocateAdvance(Request $request, int|string $id): JsonResponse
    {
        $payload = $this->withContext($request);
        $sourceType = (string) ($payload['source_type'] ?? '');
        $sourceId = (int) ($payload['source_id'] ?? 0);
        if ($sourceType === '' || $sourceId < 1) {
            return response()->json(['message' => 'source_type and source_id are required.'], 422);
        }

        $payload['advance_payment_id'] = (int) $id;

        return $this->respond($this->integration->applySourceAdvance($sourceType, $sourceId, $payload));
    }

    public function refund(Request $request): JsonResponse
    {
        $payload = $this->withContext($request);
        $paymentId = (int) ($payload['payment_id'] ?? 0);
        if ($paymentId < 1) {
            return response()->json(['message' => 'payment_id is required.'], 422);
        }

        return $this->respond($this->integration->refundPayment($paymentId, $payload));
    }

    public function writeOff(Request $request): JsonResponse
    {
        return $this->respond($this->createWriteOff->execute($this->withContext($request)));
    }

    public function supplierOutstanding(Request $request): JsonResponse
    {
        $payload = $this->withContext($request);
        $tenantId = (int) ($payload['tenant_id'] ?? 0);
        $supplierId = isset($payload['supplier_id']) ? (int) $payload['supplier_id'] : null;

        return $this->respond($this->integration->supplierPayables($tenantId, $supplierId));
    }

    public function invoicePaymentStatus(Request $request): JsonResponse
    {
        $payload = $this->withContext($request);
        $sourceType = (string) ($payload['source_type'] ?? '');
        $sourceId = (int) ($payload['source_id'] ?? 0);
        if ($sourceType === '' || $sourceId < 1) {
            return response()->json(['message' => 'source_type and source_id are required.'], 422);
        }

        return $this->respond($this->integration->sourcePaymentSummary($sourceType, $sourceId, $payload));
    }

    public function previewPaymentAllocation(Request $request): JsonResponse
    {
        $payload = $this->withContext($request);
        $sourceType = (string) ($payload['source_type'] ?? '');
        $sourceId = (int) ($payload['source_id'] ?? 0);
        $allocateAmount = round((float) ($payload['allocated_amount'] ?? 0), 4);
        if ($sourceType === '' || $sourceId < 1 || $allocateAmount <= 0) {
            return response()->json([
                'message' => 'source_type, source_id and allocated_amount are required.',
            ], 422);
        }

        $summaryResult = $this->integration->sourcePaymentSummary($sourceType, $sourceId, $payload);
        if ($summaryResult->isFailure()) {
            return $this->respond($summaryResult);
        }

        $summary = $summaryResult->valueOrFail();
        $outstanding = round((float) ($summary['outstanding_amount'] ?? 0), 4);

        return response()->json([
            'data' => [
                'can_allocate' => $allocateAmount <= $outstanding + 0.0001,
                'requested_amount' => $allocateAmount,
                'outstanding_amount' => $outstanding,
                'remaining_after_allocation' => round(max(0.0, $outstanding - $allocateAmount), 4),
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function withContext(Request $request): array
    {
        $payload = $request->all();

        $tenantId = $request->attributes->get((string) config('core.current_tenant.id_attribute', 'current_tenant_id'));
        if (! isset($payload['tenant_id']) && is_int($tenantId)) {
            $payload['tenant_id'] = $tenantId;
        }

        $organizationUnitId = $request->attributes->get(
            (string) config('core.current_organization_unit.id_attribute', 'current_organization_unit_id')
        );
        if (! isset($payload['organization_unit_id']) && is_int($organizationUnitId)) {
            $payload['organization_unit_id'] = $organizationUnitId;
        }

        $currentUserId = $request->attributes->get(
            (string) config('core.current_user.id_attribute', 'current_user_id')
        );
        if (! isset($payload['actor_id']) && is_int($currentUserId)) {
            $payload['actor_id'] = $currentUserId;
        }

        return $payload;
    }

    private function respond(Result $result): JsonResponse
    {
        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $statusCode = $error->code === 'PURCHASE_NOT_FOUND' || $error->code === 'PAYMENT_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message, 'code' => $error->code], $statusCode);
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }
}
