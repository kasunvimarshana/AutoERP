<?php
namespace Modules\Warehouse\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Warehouse\Application\Contracts\CreateWarehouseZoneServiceInterface;
use Modules\Warehouse\Application\Contracts\DeleteWarehouseZoneServiceInterface;
use Modules\Warehouse\Application\Contracts\UpdateWarehouseZoneServiceInterface;
use Modules\Warehouse\Application\DTOs\WarehouseZoneData;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseZoneRepositoryInterface;
use Modules\Warehouse\Infrastructure\Http\Resources\WarehouseZoneResource;

class WarehouseZoneController extends Controller
{
    public function __construct(
        private readonly WarehouseZoneRepositoryInterface $repository,
        private readonly CreateWarehouseZoneServiceInterface $createService,
        private readonly UpdateWarehouseZoneServiceInterface $updateService,
        private readonly DeleteWarehouseZoneServiceInterface $deleteService,
    ) {}

    public function index(Request $request, int $warehouseId): JsonResponse
    {
        $filters = $request->only(['status', 'type']);
        $perPage = (int) $request->query('per_page', 15);
        $zones   = $this->repository->findByWarehouse($warehouseId, $filters, $perPage);
        return response()->json($zones);
    }

    public function store(Request $request, int $warehouseId): JsonResponse
    {
        $data = new WarehouseZoneData(
            warehouseId: $warehouseId,
            code:        $request->input('code'),
            name:        $request->input('name'),
            type:        $request->input('type', 'storage'),
            status:      $request->input('status', 'active'),
            description: $request->input('description'),
        );
        $zone = $this->createService->execute($data);
        return response()->json(new WarehouseZoneResource($zone), 201);
    }

    public function show(int $id): JsonResponse
    {
        $zone = $this->repository->findById($id);
        if (!$zone) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json(new WarehouseZoneResource($zone));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $zone = $this->repository->findById($id);
        if (!$zone) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $data = new WarehouseZoneData(
            warehouseId: $zone->warehouseId,
            code:        $request->input('code', $zone->code),
            name:        $request->input('name', $zone->name),
            type:        $request->input('type', $zone->type),
            status:      $request->input('status', $zone->status),
            description: $request->input('description', $zone->description),
        );
        $updated = $this->updateService->execute($zone, $data);
        return response()->json(new WarehouseZoneResource($updated));
    }

    public function destroy(int $id): JsonResponse
    {
        $zone = $this->repository->findById($id);
        if (!$zone) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $this->deleteService->execute($zone);
        return response()->json(null, 204);
    }
}
