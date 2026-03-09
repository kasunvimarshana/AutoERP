<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Contracts\SagaOrchestratorInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Saga Controller
 *
 * Exposes saga orchestration endpoints for starting, querying, and managing
 * distributed transactions across microservices.
 */
class SagaController extends Controller
{
    public function __construct(
        protected readonly SagaOrchestratorInterface $orchestrator
    ) {}

    /**
     * Get the status of a saga transaction.
     */
    public function status(string $sagaId): JsonResponse
    {
        $status = $this->orchestrator->getStatus($sagaId);

        if (!$status) {
            return response()->json(['success' => false, 'message' => 'Saga not found.'], 404);
        }

        return response()->json(['success' => true, 'data' => $status]);
    }

    /**
     * Trigger manual compensation for a failed saga.
     */
    public function compensate(Request $request, string $sagaId): JsonResponse
    {
        $request->validate([
            'failed_step' => ['required', 'string'],
        ]);

        $saga = \App\Domain\Entities\SagaRecord::where('saga_id', $sagaId)->first();

        if (!$saga) {
            return response()->json(['success' => false, 'message' => 'Saga not found.'], 404);
        }

        $this->orchestrator->compensate(
            $sagaId,
            $request->string('failed_step')->toString(),
            $saga->context ?? []
        );

        return response()->json(['success' => true, 'message' => 'Compensation triggered.']);
    }

    /**
     * List all saga transactions with optional status filter.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'saga_type', 'per_page', 'page']);

        $query = \App\Domain\Entities\SagaRecord::query()
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(isset($filters['saga_type']), fn ($q) => $q->where('saga_type', $filters['saga_type']))
            ->orderBy('created_at', 'desc');

        if (isset($filters['per_page'])) {
            $result = $query->paginate(
                (int) $filters['per_page'],
                ['*'],
                'page',
                (int) ($filters['page'] ?? 1)
            );

            return response()->json([
                'success' => true,
                'data' => $result->items(),
                'meta' => [
                    'current_page' => $result->currentPage(),
                    'last_page' => $result->lastPage(),
                    'per_page' => $result->perPage(),
                    'total' => $result->total(),
                ],
            ]);
        }

        return response()->json(['success' => true, 'data' => $query->get()]);
    }
}
