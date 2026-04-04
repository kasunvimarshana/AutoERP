<?php
namespace Modules\Warehouse\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Warehouse\Application\Contracts\CreateWarehouseLocationServiceInterface;
use Modules\Warehouse\Application\Contracts\DeleteWarehouseLocationServiceInterface;
use Modules\Warehouse\Application\Contracts\UpdateWarehouseLocationServiceInterface;
use Modules\Warehouse\Application\DTOs\WarehouseLocationData;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseLocationRepositoryInterface;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseZoneRepositoryInterface;
use Modules\Warehouse\Infrastructure\Http\Resources\WarehouseLocationResource;

class WarehouseLocationController extends Controller
{
    public function __construct(
        private readonly WarehouseLocationRepositoryInterface $repository,
        private readonly WarehouseZoneRepositoryInterface $zoneRepository,
        private readonly CreateWarehouseLocationServiceInterface $createService,
        private readonly UpdateWarehouseLocationServiceInterface $updateService,
        private readonly DeleteWarehouseLocationServiceInterface $deleteService,
    ) {}

    public function index(Request $request, int $zoneId): JsonResponse
    {
        $filters   = $request->only(['is_active', 'location_type']);
        $perPage   = (int) $request->query('per_page', 15);
        $locations = $this->repository->findByZone($zoneId, $filters, $perPage);
        return response()->json($locations);
    }

    public function store(Request $request, int $zoneId): JsonResponse
    {
        $zone = $this->zoneRepository->findById($zoneId);
        if (!$zone) {
            return response()->json(['message' => 'Zone not found'], 404);
        }
        $data = new WarehouseLocationData(
            warehouseId:  $zone->warehouseId,
            zoneId:       $zoneId,
            code:         $request->input('code'),
            barcode:      $request->input('barcode'),
            locationType: $request->input('location_type', 'shelf'),
            isActive:     (bool) $request->input('is_active', true),
            aisle:        $request->input('aisle'),
            bay:          $request->input('bay'),
            level:        $request->input('level'),
            bin:          $request->input('bin'),
            maxWeight:    $request->input('max_weight') !== null ? (float) $request->input('max_weight') : null,
            maxVolume:    $request->input('max_volume') !== null ? (float) $request->input('max_volume') : null,
        );
        $location = $this->createService->execute($data);
        return response()->json(new WarehouseLocationResource($location), 201);
    }

    public function show(int $id): JsonResponse
    {
        $location = $this->repository->findById($id);
        if (!$location) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json(new WarehouseLocationResource($location));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $location = $this->repository->findById($id);
        if (!$location) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $data = new WarehouseLocationData(
            warehouseId:  $location->warehouseId,
            zoneId:       $location->zoneId,
            code:         $request->input('code', $location->code),
            barcode:      $request->input('barcode', $location->barcode),
            locationType: $request->input('location_type', $location->locationType),
            isActive:     $request->input('is_active', $location->isActive),
            aisle:        $request->input('aisle', $location->aisle),
            bay:          $request->input('bay', $location->bay),
            level:        $request->input('level', $location->level),
            bin:          $request->input('bin', $location->bin),
            maxWeight:    $request->input('max_weight') !== null ? (float) $request->input('max_weight') : $location->maxWeight,
            maxVolume:    $request->input('max_volume') !== null ? (float) $request->input('max_volume') : $location->maxVolume,
        );
        $updated = $this->updateService->execute($location, $data);
        return response()->json(new WarehouseLocationResource($updated));
    }

    public function destroy(int $id): JsonResponse
    {
        $location = $this->repository->findById($id);
        if (!$location) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $this->deleteService->execute($location);
        return response()->json(null, 204);
    }
}
