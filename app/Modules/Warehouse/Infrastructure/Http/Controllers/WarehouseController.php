<?php
namespace Modules\Warehouse\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Warehouse\Application\Contracts\CreateWarehouseServiceInterface;
use Modules\Warehouse\Application\Contracts\DeleteWarehouseServiceInterface;
use Modules\Warehouse\Application\Contracts\UpdateWarehouseServiceInterface;
use Modules\Warehouse\Application\DTOs\WarehouseData;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseRepositoryInterface;
use Modules\Warehouse\Infrastructure\Http\Resources\WarehouseResource;

class WarehouseController extends Controller
{
    public function __construct(
        private readonly WarehouseRepositoryInterface $repository,
        private readonly CreateWarehouseServiceInterface $createService,
        private readonly UpdateWarehouseServiceInterface $updateService,
        private readonly DeleteWarehouseServiceInterface $deleteService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->query('tenant_id', 0);
        $filters  = $request->only(['status', 'type']);
        $perPage  = (int) $request->query('per_page', 15);

        $warehouses = $this->repository->findAll($tenantId, $filters, $perPage);
        return response()->json($warehouses);
    }

    public function store(Request $request): JsonResponse
    {
        $data = new WarehouseData(
            tenantId:  (int) $request->input('tenant_id'),
            code:      $request->input('code'),
            name:      $request->input('name'),
            type:      $request->input('type', 'standard'),
            status:    $request->input('status', 'active'),
            address:   $request->input('address'),
            city:      $request->input('city'),
            country:   $request->input('country'),
            isDefault: (bool) $request->input('is_default', false),
        );
        $warehouse = $this->createService->execute($data);
        return response()->json(new WarehouseResource($warehouse), 201);
    }

    public function show(int $id): JsonResponse
    {
        $warehouse = $this->repository->findById($id);
        if (!$warehouse) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json(new WarehouseResource($warehouse));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $warehouse = $this->repository->findById($id);
        if (!$warehouse) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $data = new WarehouseData(
            tenantId:  $request->input('tenant_id', $warehouse->tenantId),
            code:      $request->input('code', $warehouse->code),
            name:      $request->input('name', $warehouse->name),
            type:      $request->input('type', $warehouse->type),
            status:    $request->input('status', $warehouse->status),
            address:   $request->input('address', $warehouse->address),
            city:      $request->input('city', $warehouse->city),
            country:   $request->input('country', $warehouse->country),
            isDefault: $request->input('is_default', $warehouse->isDefault),
        );
        $updated = $this->updateService->execute($warehouse, $data);
        return response()->json(new WarehouseResource($updated));
    }

    public function destroy(int $id): JsonResponse
    {
        $warehouse = $this->repository->findById($id);
        if (!$warehouse) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $this->deleteService->execute($warehouse);
        return response()->json(null, 204);
    }
}
