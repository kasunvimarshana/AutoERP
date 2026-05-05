<?php declare(strict_types=1);

namespace Modules\Asset\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Asset\Application\Contracts\ManageVehicleServiceInterface;
use Modules\Asset\Infrastructure\Http\Requests\CreateVehicleRequest;
use Modules\Asset\Infrastructure\Http\Requests\UpdateVehicleRequest;
use Modules\Asset\Infrastructure\Http\Resources\VehicleResource;

class VehicleController extends Controller
{
    public function __construct(
        private readonly ManageVehicleServiceInterface $service,
    ) {}

    public function create(CreateVehicleRequest $request): JsonResponse
    {
        $vehicle = $this->service->create($request->validated());
        return response()->json(new VehicleResource($vehicle), 201);
    }

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $result = $this->service->list($tenantId, (int) ($request->query('per_page', 15)), (int) ($request->query('page', 1)));
        return response()->json($result);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $vehicle = $this->service->find($tenantId, $id);
        return response()->json(new VehicleResource($vehicle));
    }

    public function update(UpdateVehicleRequest $request, string $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $vehicle = $this->service->update($tenantId, $id, $request->validated());
        return response()->json(new VehicleResource($vehicle));
    }

    public function delete(Request $request, string $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $this->service->delete($tenantId, $id);
        return response()->json(null, 204);
    }

    public function availableForRental(Request $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $vehicles = $this->service->getAvailableForRental($tenantId);
        return response()->json(['data' => $vehicles]);
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $vehicle = $this->service->updateStatus($tenantId, $id, $request->string('status')->toString());
        return response()->json(new VehicleResource($vehicle));
    }

    public function updateMileage(Request $request, string $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $vehicle = $this->service->updateMileage($tenantId, $id, (int) $request->input('current_mileage'));
        return response()->json(new VehicleResource($vehicle));
    }
}
