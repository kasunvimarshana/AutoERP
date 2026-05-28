<?php

declare(strict_types=1);

namespace Modules\Sales\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\Results\Result;
use Modules\Sales\Application\Contracts\Services\SalesIntegrationServiceInterface;
use Modules\Sales\Presentation\Http\Requests\SalesIntegrationActionRequest;

final class SalesIntegrationController extends Controller
{
    public function __construct(private readonly SalesIntegrationServiceInterface $service)
    {
    }

    public function listDocuments(
        SalesIntegrationActionRequest $request,
        string $entityType,
        int|string $id,
    ): JsonResponse {
        return $this->respond($this->service->listSourceDocuments($entityType, $id, $this->withContext($request)));
    }

    public function showDocument(
        SalesIntegrationActionRequest $request,
        string $entityType,
        int|string $id,
        int $documentId,
    ): JsonResponse {
        return $this->respond(
            $this->service->showSourceDocument($entityType, $id, $documentId, $this->withContext($request))
        );
    }

    public function createDocument(
        SalesIntegrationActionRequest $request,
        string $entityType,
        int|string $id,
    ): JsonResponse {
        return $this->respond($this->service->createSourceDocument($entityType, $id, $this->withContext($request)));
    }

    public function changeDocumentStatus(
        SalesIntegrationActionRequest $request,
        string $entityType,
        int|string $id,
        int $documentId,
    ): JsonResponse {
        return $this->respond(
            $this->service->changeSourceDocumentStatus($entityType, $id, $documentId, $this->withContext($request))
        );
    }

    public function matchDocumentLine(
        SalesIntegrationActionRequest $request,
        string $entityType,
        int|string $id,
        int $documentId,
    ): JsonResponse {
        return $this->respond(
            $this->service->matchSourceDocumentLine($entityType, $id, $documentId, $this->withContext($request))
        );
    }

    public function unmatchDocumentLine(
        SalesIntegrationActionRequest $request,
        string $entityType,
        int|string $id,
        int $documentId,
    ): JsonResponse {
        return $this->respond(
            $this->service->unmatchSourceDocumentLine($entityType, $id, $documentId, $this->withContext($request))
        );
    }

    public function createPayment(
        SalesIntegrationActionRequest $request,
        string $entityType,
        int|string $id,
    ): JsonResponse {
        return $this->respond($this->service->createSourcePayment($entityType, $id, $this->withContext($request)));
    }

    public function createAdvance(
        SalesIntegrationActionRequest $request,
        string $entityType,
        int|string $id,
    ): JsonResponse {
        return $this->respond($this->service->createSourceAdvance($entityType, $id, $this->withContext($request)));
    }

    public function allocatePayment(
        SalesIntegrationActionRequest $request,
        string $entityType,
        int|string $id,
    ): JsonResponse {
        return $this->respond($this->service->allocateSourcePayment($entityType, $id, $this->withContext($request)));
    }

    public function applyAdvance(
        SalesIntegrationActionRequest $request,
        string $entityType,
        int|string $id,
    ): JsonResponse {
        return $this->respond($this->service->applySourceAdvance($entityType, $id, $this->withContext($request)));
    }

    public function listPaymentAllocations(
        SalesIntegrationActionRequest $request,
        string $entityType,
        int|string $id,
    ): JsonResponse {
        return $this->respond(
            $this->service->listSourcePaymentAllocations($entityType, $id, $this->withContext($request))
        );
    }

    public function sourcePaymentSummary(
        SalesIntegrationActionRequest $request,
        string $entityType,
        int|string $id,
    ): JsonResponse {
        return $this->respond($this->service->sourcePaymentSummary($entityType, $id, $this->withContext($request)));
    }

    public function customerReceivables(SalesIntegrationActionRequest $request): JsonResponse
    {
        $payload = $this->withContext($request);
        $tenantId = (int) ($payload['tenant_id'] ?? 0);
        $customerId = isset($payload['customer_id']) ? (int) $payload['customer_id'] : null;

        return $this->respond($this->service->customerReceivables($tenantId, $customerId));
    }

    public function customerAdvanceBalances(SalesIntegrationActionRequest $request): JsonResponse
    {
        $payload = $this->withContext($request);
        $tenantId = (int) ($payload['tenant_id'] ?? 0);
        $customerId = isset($payload['customer_id']) ? (int) $payload['customer_id'] : null;

        return $this->respond($this->service->customerAdvanceBalances($tenantId, $customerId));
    }

    public function postPayment(SalesIntegrationActionRequest $request, int|string $paymentId): JsonResponse
    {
        return $this->respond($this->service->postPayment($paymentId, $this->withContext($request)));
    }

    public function reversePayment(SalesIntegrationActionRequest $request, int|string $paymentId): JsonResponse
    {
        return $this->respond($this->service->reversePayment($paymentId, $this->withContext($request)));
    }

    public function refundPayment(SalesIntegrationActionRequest $request, int|string $paymentId): JsonResponse
    {
        return $this->respond($this->service->refundPayment($paymentId, $this->withContext($request)));
    }

    /** @return array<string, mixed> */
    private function withContext(SalesIntegrationActionRequest $request): array
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

    private function respond(Result $result): JsonResponse
    {
        if ($result->isFailure()) {
            $error = $result->errorOrFail();
            $statusCode = $error->code === 'SALES_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $error->message, 'code' => $error->code], $statusCode);
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }
}
