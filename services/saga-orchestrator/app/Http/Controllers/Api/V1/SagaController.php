<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Repositories\SagaRepositoryInterface;
use App\Contracts\Saga\SagaOrchestratorInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Saga\StartSagaRequest;
use App\Http\Resources\Saga\SagaTransactionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Saga Controller - Thin HTTP layer for Saga orchestration.
 * Business logic delegated to SagaOrchestrator.
 */
class SagaController extends Controller
{
    public function __construct(
        private readonly SagaOrchestratorInterface $orchestrator,
        private readonly SagaRepositoryInterface $sagaRepository
    ) {}

    /**
     * Start a new distributed saga transaction.
     *
     * This is the single entry point to initiate a distributed transaction
     * that spans multiple microservices with automatic rollback on failure.
     */
    public function start(StartSagaRequest $request): JsonResponse
    {
        $data = $request->validated();
        $tenantId = $request->input('_tenant_id', $data['tenant_id'] ?? '');

        $saga = $this->orchestrator->start(
            $data['saga_type'],
            $data['payload'],
            $tenantId
        );

        return (new SagaTransactionResource($saga))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get saga transaction status and step details.
     */
    public function show(Request $request, string $sagaId): JsonResponse
    {
        $saga = $this->orchestrator->getStatus($sagaId);

        return (new SagaTransactionResource($saga))->response();
    }

    /**
     * List sagas for the current tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->input('_tenant_id');
        $sagas = $this->sagaRepository->getByTenant($tenantId, $request->query());

        return response()->json([
            'success' => true,
            'data' => SagaTransactionResource::collection($sagas),
        ]);
    }
}
