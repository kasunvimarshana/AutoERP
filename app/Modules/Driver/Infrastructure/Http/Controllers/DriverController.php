<?php declare(strict_types=1);

namespace Modules\Driver\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Driver\Application\Contracts\ManageDriverServiceInterface;
use Modules\Driver\Infrastructure\Http\Requests\CreateDriverRequest;
use Modules\Driver\Infrastructure\Http\Requests\UpdateDriverRequest;
use Modules\Driver\Infrastructure\Http\Resources\DriverResource;

class DriverController extends Controller
{
    public function __construct(
        private readonly ManageDriverServiceInterface $service,
    ) {}

    public function create(CreateDriverRequest $request): JsonResponse
    {
        $driver = $this->service->create($request->validated());
        return response()->json(new DriverResource($driver), 201);
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
        $driver = $this->service->find($tenantId, $id);
        return response()->json(new DriverResource($driver));
    }

    public function update(UpdateDriverRequest $request, string $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $driver = $this->service->update($tenantId, $id, $request->validated());
        return response()->json(new DriverResource($driver));
    }

    public function delete(Request $request, string $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $this->service->delete($tenantId, $id);
        return response()->json(null, 204);
    }

    public function available(Request $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $drivers = $this->service->getAvailable($tenantId);
        return response()->json(['data' => $drivers]);
    }
}
