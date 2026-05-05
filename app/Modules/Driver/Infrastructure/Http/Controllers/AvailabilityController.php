<?php declare(strict_types=1);

namespace Modules\Driver\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Driver\Application\Contracts\ManageAvailabilityServiceInterface;
use Modules\Driver\Infrastructure\Http\Requests\CreateAvailabilityRequest;
use Modules\Driver\Infrastructure\Http\Requests\UpdateAvailabilityRequest;
use Modules\Driver\Infrastructure\Http\Resources\AvailabilityResource;

class AvailabilityController extends Controller
{
    public function __construct(
        private readonly ManageAvailabilityServiceInterface $service,
    ) {}

    public function create(CreateAvailabilityRequest $request): JsonResponse
    {
        $availability = $this->service->create($request->validated());
        return response()->json(new AvailabilityResource($availability), 201);
    }

    public function getByDriver(Request $request, string $driverId): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $records = $this->service->getByDriver($tenantId, $driverId);
        return response()->json(['data' => $records]);
    }

    public function update(UpdateAvailabilityRequest $request, string $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $availability = $this->service->update($tenantId, $id, $request->validated());
        return response()->json(new AvailabilityResource($availability));
    }
}
