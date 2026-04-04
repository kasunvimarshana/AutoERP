<?php
namespace Modules\Inventory\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Inventory\Application\Contracts\AdjustInventoryServiceInterface;
use Modules\Inventory\Application\Contracts\ReleaseStockServiceInterface;
use Modules\Inventory\Application\Contracts\ReserveStockServiceInterface;
use Modules\Inventory\Application\DTOs\AdjustInventoryData;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLevelRepositoryInterface;
use Modules\Inventory\Infrastructure\Http\Resources\InventoryLevelResource;

class InventoryLevelController extends Controller
{
    public function __construct(
        private readonly InventoryLevelRepositoryInterface $repository,
        private readonly ReserveStockServiceInterface $reserveService,
        private readonly ReleaseStockServiceInterface $releaseService,
        private readonly AdjustInventoryServiceInterface $adjustService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->query('tenant_id', 0);
        $levels = $this->repository->findAll($tenantId, $request->only(['warehouse_id', 'product_id', 'stock_status']));
        return response()->json($levels);
    }

    public function show(int $id): JsonResponse
    {
        $level = $this->repository->findById($id);
        if (!$level) return response()->json(['message' => 'Not found'], 404);
        return response()->json(new InventoryLevelResource($level));
    }

    public function reserve(Request $request, int $id): JsonResponse
    {
        $request->validate(['quantity' => 'required|numeric|min:0.0001']);
        try {
            $level = $this->reserveService->execute($id, (float) $request->input('quantity'));
            return response()->json(new InventoryLevelResource($level));
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function release(Request $request, int $id): JsonResponse
    {
        $request->validate(['quantity' => 'required|numeric|min:0.0001']);
        try {
            $level = $this->releaseService->execute($id, (float) $request->input('quantity'));
            return response()->json(new InventoryLevelResource($level));
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function adjust(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'new_quantity' => 'required|numeric|min:0',
            'reason'       => 'required|string',
        ]);

        $level = $this->repository->findById($id);
        if (!$level) return response()->json(['message' => 'Not found'], 404);

        $data = new AdjustInventoryData(
            tenantId: $level->tenantId,
            productId: $level->productId,
            warehouseId: $level->warehouseId,
            locationId: $level->locationId,
            newQuantity: (float) $request->input('new_quantity'),
            reason: $request->input('reason'),
            batchId: $level->batchId,
        );

        $updated = $this->adjustService->execute($data);
        return response()->json(new InventoryLevelResource($updated));
    }
}
