<?php

declare(strict_types=1);

namespace Modules\Sales\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\Results\Result;
use Modules\Sales\Application\Contracts\Services\SalesWorkflowServiceInterface;
use Modules\Sales\Presentation\Http\Requests\SalesWorkflowActionRequest;

final class SalesWorkflowController extends Controller
{
    public function __construct(private readonly SalesWorkflowServiceInterface $workflowService)
    {
    }

    public function transition(SalesWorkflowActionRequest $request, string $entityType, int|string $id): JsonResponse
    {
        return $this->respond(
            $this->workflowService->transition($entityType, $id, $this->withTenantContext($request))
        );
    }

    public function createDocument(
        SalesWorkflowActionRequest $request,
        string $entityType,
        int|string $id
    ): JsonResponse {
        return $this->respond(
            $this->workflowService->createDocument($entityType, $id, $this->withTenantContext($request))
        );
    }

    public function allocatePayment(
        SalesWorkflowActionRequest $request,
        string $entityType,
        int|string $id
    ): JsonResponse {
        return $this->respond(
            $this->workflowService->allocatePayment($entityType, $id, $this->withTenantContext($request))
        );
    }

    public function postInventory(
        SalesWorkflowActionRequest $request,
        string $entityType,
        int|string $id
    ): JsonResponse {
        return $this->respond(
            $this->workflowService->postInventory($entityType, $id, $this->withTenantContext($request))
        );
    }

    public function postFinance(
        SalesWorkflowActionRequest $request,
        string $entityType,
        int|string $id
    ): JsonResponse {
        return $this->respond(
            $this->workflowService->postFinance($entityType, $id, $this->withTenantContext($request))
        );
    }

    public function reverseFinance(
        SalesWorkflowActionRequest $request,
        string $entityType,
        int|string $id
    ): JsonResponse {
        return $this->respond(
            $this->workflowService->reverseFinance($entityType, $id, $this->withTenantContext($request))
        );
    }

    /** @return array<string, mixed> */
    private function withTenantContext(SalesWorkflowActionRequest $request): array
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

            return response()->json(['message' => $error->message], $statusCode);
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }
}
