<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Purchase\Application\Contracts\Services\PurchaseIntegrationServiceInterface;
use Modules\Purchase\Presentation\Http\Controllers\Concerns\RespondsWithPurchaseResult;
use Modules\Purchase\Presentation\Http\Requests\PurchaseIntegrationActionRequest;

final class PurchaseIntegrationController extends Controller
{
    use RespondsWithPurchaseResult;

    public function __construct(private readonly PurchaseIntegrationServiceInterface $service) {}

    public function listDocuments(
        PurchaseIntegrationActionRequest $request,
        string $entityType,
        int|string $id,
    ): JsonResponse {
        return $this->respond($this->service->listSourceDocuments($entityType, $id, $this->withContext($request)), true);
    }

    public function showDocument(
        PurchaseIntegrationActionRequest $request,
        string $entityType,
        int|string $id,
        int $documentId,
    ): JsonResponse {
        return $this->respond(
            $this->service->showSourceDocument($entityType, $id, $documentId, $this->withContext($request)),
            true,
        );
    }

    public function createDocument(
        PurchaseIntegrationActionRequest $request,
        string $entityType,
        int|string $id,
    ): JsonResponse {
        return $this->respond($this->service->createSourceDocument($entityType, $id, $this->withContext($request)), true);
    }

    public function changeDocumentStatus(
        PurchaseIntegrationActionRequest $request,
        string $entityType,
        int|string $id,
        int $documentId,
    ): JsonResponse {
        return $this->respond(
            $this->service->changeSourceDocumentStatus($entityType, $id, $documentId, $this->withContext($request)),
            true,
        );
    }

    public function matchDocumentLine(
        PurchaseIntegrationActionRequest $request,
        string $entityType,
        int|string $id,
        int $documentId,
    ): JsonResponse {
        return $this->respond(
            $this->service->matchSourceDocumentLine($entityType, $id, $documentId, $this->withContext($request)),
            true,
        );
    }

    public function unmatchDocumentLine(
        PurchaseIntegrationActionRequest $request,
        string $entityType,
        int|string $id,
        int $documentId,
    ): JsonResponse {
        return $this->respond(
            $this->service->unmatchSourceDocumentLine($entityType, $id, $documentId, $this->withContext($request)),
            true,
        );
    }

    public function createPayment(
        PurchaseIntegrationActionRequest $request,
        string $entityType,
        int|string $id,
    ): JsonResponse {
        return $this->respond($this->service->createSourcePayment($entityType, $id, $this->withContext($request)), true);
    }

    public function createAdvance(
        PurchaseIntegrationActionRequest $request,
        string $entityType,
        int|string $id,
    ): JsonResponse {
        return $this->respond($this->service->createSourceAdvance($entityType, $id, $this->withContext($request)), true);
    }

    public function allocatePayment(
        PurchaseIntegrationActionRequest $request,
        string $entityType,
        int|string $id,
    ): JsonResponse {
        return $this->respond($this->service->allocateSourcePayment($entityType, $id, $this->withContext($request)), true);
    }

    public function applyAdvance(
        PurchaseIntegrationActionRequest $request,
        string $entityType,
        int|string $id,
    ): JsonResponse {
        return $this->respond($this->service->applySourceAdvance($entityType, $id, $this->withContext($request)), true);
    }

    public function listPaymentAllocations(
        PurchaseIntegrationActionRequest $request,
        string $entityType,
        int|string $id,
    ): JsonResponse {
        return $this->respond(
            $this->service->listSourcePaymentAllocations($entityType, $id, $this->withContext($request)),
            true,
        );
    }

    public function sourcePaymentSummary(
        PurchaseIntegrationActionRequest $request,
        string $entityType,
        int|string $id,
    ): JsonResponse {
        return $this->respond($this->service->sourcePaymentSummary($entityType, $id, $this->withContext($request)), true);
    }

    public function supplierPayables(PurchaseIntegrationActionRequest $request): JsonResponse
    {
        $payload = $this->withContext($request);
        $tenantId = (int) ($payload['tenant_id'] ?? 0);
        $supplierId = isset($payload['supplier_id']) ? (int) $payload['supplier_id'] : null;

        return $this->respond($this->service->supplierPayables($tenantId, $supplierId), true);
    }

    public function supplierAdvanceBalances(PurchaseIntegrationActionRequest $request): JsonResponse
    {
        $payload = $this->withContext($request);
        $tenantId = (int) ($payload['tenant_id'] ?? 0);
        $supplierId = isset($payload['supplier_id']) ? (int) $payload['supplier_id'] : null;

        return $this->respond($this->service->supplierAdvanceBalances($tenantId, $supplierId), true);
    }

    public function postPayment(PurchaseIntegrationActionRequest $request, int|string $paymentId): JsonResponse
    {
        return $this->respond($this->service->postPayment($paymentId, $this->withContext($request)), true);
    }

    public function reversePayment(PurchaseIntegrationActionRequest $request, int|string $paymentId): JsonResponse
    {
        return $this->respond($this->service->reversePayment($paymentId, $this->withContext($request)), true);
    }

    public function refundPayment(PurchaseIntegrationActionRequest $request, int|string $paymentId): JsonResponse
    {
        return $this->respond($this->service->refundPayment($paymentId, $this->withContext($request)), true);
    }

    /** @return array<string, mixed> */
    private function withContext(PurchaseIntegrationActionRequest $request): array
    {
        $payload = $request->validated();

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
}
