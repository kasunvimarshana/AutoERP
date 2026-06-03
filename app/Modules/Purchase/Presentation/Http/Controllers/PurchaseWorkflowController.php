<?php

declare(strict_types=1);

namespace Modules\Purchase\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Purchase\Application\Contracts\Services\PurchaseWorkflowServiceInterface;
use Modules\Purchase\Presentation\Http\Controllers\Concerns\RespondsWithPurchaseResult;
use Modules\Purchase\Presentation\Http\Requests\PurchaseWorkflowActionRequest;

final class PurchaseWorkflowController extends Controller
{
    use RespondsWithPurchaseResult;

    public function __construct(private readonly PurchaseWorkflowServiceInterface $workflowService) {}

    public function transition(PurchaseWorkflowActionRequest $request, string $entityType, int|string $id): JsonResponse
    {
        return $this->respond(
            $this->workflowService->transition($entityType, $id, $this->withTenantContext($request))
        );
    }

    public function createDocument(
        PurchaseWorkflowActionRequest $request,
        string $entityType,
        int|string $id
    ): JsonResponse {
        return $this->respond(
            $this->workflowService->createDocument($entityType, $id, $this->withTenantContext($request))
        );
    }

    public function allocatePayment(
        PurchaseWorkflowActionRequest $request,
        string $entityType,
        int|string $id
    ): JsonResponse {
        return $this->respond(
            $this->workflowService->allocatePayment($entityType, $id, $this->withTenantContext($request))
        );
    }

    public function postInventory(
        PurchaseWorkflowActionRequest $request,
        string $entityType,
        int|string $id
    ): JsonResponse {
        return $this->respond(
            $this->workflowService->postInventory($entityType, $id, $this->withTenantContext($request))
        );
    }

    public function postFinance(
        PurchaseWorkflowActionRequest $request,
        string $entityType,
        int|string $id
    ): JsonResponse {
        return $this->respond(
            $this->workflowService->postFinance($entityType, $id, $this->withTenantContext($request))
        );
    }

    public function reverseFinance(
        PurchaseWorkflowActionRequest $request,
        string $entityType,
        int|string $id
    ): JsonResponse {
        return $this->respond(
            $this->workflowService->reverseFinance($entityType, $id, $this->withTenantContext($request))
        );
    }

    /** @return array<string, mixed> */
    private function withTenantContext(PurchaseWorkflowActionRequest $request): array
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
