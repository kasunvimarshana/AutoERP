<?php

namespace Modules\Returns\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Returns\Application\Contracts\ApproveStockReturnServiceInterface;
use Modules\Returns\Application\Contracts\CancelStockReturnServiceInterface;
use Modules\Returns\Application\Contracts\CompleteStockReturnServiceInterface;
use Modules\Returns\Application\Contracts\CreateStockReturnServiceInterface;
use Modules\Returns\Application\Contracts\IssueCreditMemoServiceInterface;
use Modules\Returns\Application\DTOs\StockReturnData;
use Modules\Returns\Domain\RepositoryInterfaces\StockReturnRepositoryInterface;
use Modules\Returns\Infrastructure\Http\Resources\StockReturnResource;

class StockReturnController extends Controller
{
    public function __construct(
        private readonly StockReturnRepositoryInterface $repository,
        private readonly CreateStockReturnServiceInterface $createService,
        private readonly ApproveStockReturnServiceInterface $approveService,
        private readonly CancelStockReturnServiceInterface $cancelService,
        private readonly CompleteStockReturnServiceInterface $completeService,
        private readonly IssueCreditMemoServiceInterface $issueCreditService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->query('tenant_id', 0);
        $returns = $this->repository->findAll(
            $tenantId,
            $request->only(['status', 'return_type', 'warehouse_id', 'customer_id', 'supplier_id'])
        );

        return response()->json($returns);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tenant_id'              => 'required|integer',
            'warehouse_id'           => 'required|integer',
            'return_number'          => 'required|string|max:100',
            'return_type'            => 'required|string|max:20',
            'lines'                  => 'nullable|array',
            'lines.*.product_id'     => 'required|integer',
            'lines.*.return_qty'     => 'required|numeric|min:0.0001',
            'lines.*.location_id'    => 'required|integer',
            'customer_id'            => 'nullable|integer',
            'supplier_id'            => 'nullable|integer',
            'original_order_id'      => 'nullable|integer',
            'original_order_type'    => 'nullable|string|max:50',
            'reason'                 => 'nullable|string',
            'notes'                  => 'nullable|string',
        ]);

        try {
            $dto = new StockReturnData(
                tenantId: $validated['tenant_id'],
                warehouseId: $validated['warehouse_id'],
                returnNumber: $validated['return_number'],
                returnType: $validated['return_type'],
                lines: $validated['lines'] ?? [],
                customerId: $validated['customer_id'] ?? null,
                supplierId: $validated['supplier_id'] ?? null,
                originalOrderId: $validated['original_order_id'] ?? null,
                originalOrderType: $validated['original_order_type'] ?? null,
                reason: $validated['reason'] ?? null,
                notes: $validated['notes'] ?? null,
            );

            $return = $this->createService->execute($dto);

            return response()->json(new StockReturnResource($return), 201);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        $return = $this->repository->findById($id);
        if (!$return) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json(new StockReturnResource($return));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $return = $this->repository->findById($id);
        if (!$return) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string',
            'notes'  => 'nullable|string',
        ]);

        $updated = $this->repository->update($return, $validated);

        return response()->json(new StockReturnResource($updated));
    }

    public function destroy(int $id): JsonResponse
    {
        $return = $this->repository->findById($id);
        if (!$return) {
            return response()->json(['message' => 'Not found'], 404);
        }

        \Modules\Returns\Infrastructure\Persistence\Eloquent\Models\StockReturnModel::findOrFail($id)->delete();

        return response()->json(null, 204);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $request->validate(['approved_by' => 'required|integer']);

        $return = $this->repository->findById($id);
        if (!$return) {
            return response()->json(['message' => 'Not found'], 404);
        }

        try {
            $updated = $this->approveService->execute($return, (int) $request->input('approved_by'));

            return response()->json(new StockReturnResource($updated));
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function cancel(int $id): JsonResponse
    {
        $return = $this->repository->findById($id);
        if (!$return) {
            return response()->json(['message' => 'Not found'], 404);
        }

        try {
            $updated = $this->cancelService->execute($return);

            return response()->json(new StockReturnResource($updated));
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function complete(Request $request, int $id): JsonResponse
    {
        $request->validate(['completed_by' => 'required|integer']);

        $return = $this->repository->findById($id);
        if (!$return) {
            return response()->json(['message' => 'Not found'], 404);
        }

        try {
            $updated = $this->completeService->execute($return, (int) $request->input('completed_by'));

            return response()->json(new StockReturnResource($updated));
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function issueCredit(int $id): JsonResponse
    {
        $return = $this->repository->findById($id);
        if (!$return) {
            return response()->json(['message' => 'Not found'], 404);
        }

        try {
            $updated = $this->issueCreditService->execute($return);

            return response()->json(new StockReturnResource($updated));
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
