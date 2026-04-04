<?php

namespace Modules\Returns\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Returns\Application\Contracts\ApproveReturnAuthorizationServiceInterface;
use Modules\Returns\Application\Contracts\CancelReturnAuthorizationServiceInterface;
use Modules\Returns\Application\Contracts\CreateReturnAuthorizationServiceInterface;
use Modules\Returns\Application\DTOs\ReturnAuthorizationData;
use Modules\Returns\Domain\RepositoryInterfaces\ReturnAuthorizationRepositoryInterface;
use Modules\Returns\Infrastructure\Http\Resources\ReturnAuthorizationResource;

class ReturnAuthorizationController extends Controller
{
    public function __construct(
        private readonly ReturnAuthorizationRepositoryInterface $repository,
        private readonly CreateReturnAuthorizationServiceInterface $createService,
        private readonly ApproveReturnAuthorizationServiceInterface $approveService,
        private readonly CancelReturnAuthorizationServiceInterface $cancelService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->query('tenant_id', 0);
        $rmas = $this->repository->findAll(
            $tenantId,
            $request->only(['status', 'stock_return_id'])
        );

        return response()->json($rmas);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id'       => 'required|integer',
            'stock_return_id' => 'required|integer',
            'rma_number'      => 'required|string|max:100',
            'expires_at'      => 'nullable|date',
            'notes'           => 'nullable|string',
        ]);

        try {
            $dto = new ReturnAuthorizationData(
                tenantId: $validated['tenant_id'],
                stockReturnId: $validated['stock_return_id'],
                rmaNumber: $validated['rma_number'],
                expiresAt: $validated['expires_at'] ?? null,
                notes: $validated['notes'] ?? null,
            );

            $rma = $this->createService->execute($dto);

            return response()->json(new ReturnAuthorizationResource($rma), 201);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        $rma = $this->repository->findById($id);
        if (!$rma) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json(new ReturnAuthorizationResource($rma));
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $request->validate(['approved_by' => 'required|integer']);

        $rma = $this->repository->findById($id);
        if (!$rma) {
            return response()->json(['message' => 'Not found'], 404);
        }

        try {
            $updated = $this->approveService->execute($rma, (int) $request->input('approved_by'));

            return response()->json(new ReturnAuthorizationResource($updated));
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function cancel(int $id): JsonResponse
    {
        $rma = $this->repository->findById($id);
        if (!$rma) {
            return response()->json(['message' => 'Not found'], 404);
        }

        try {
            $updated = $this->cancelService->execute($rma);

            return response()->json(new ReturnAuthorizationResource($updated));
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
