<?php declare(strict_types=1);

namespace Modules\Driver\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Driver\Application\Contracts\ManageLicenseServiceInterface;
use Modules\Driver\Infrastructure\Http\Requests\CreateLicenseRequest;
use Modules\Driver\Infrastructure\Http\Requests\UpdateLicenseRequest;
use Modules\Driver\Infrastructure\Http\Resources\LicenseResource;

class LicenseController extends Controller
{
    public function __construct(
        private readonly ManageLicenseServiceInterface $service,
    ) {}

    public function create(CreateLicenseRequest $request): JsonResponse
    {
        $license = $this->service->create($request->validated());
        return response()->json(new LicenseResource($license), 201);
    }

    public function getByDriver(Request $request, string $driverId): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $licenses = $this->service->getByDriver($tenantId, $driverId);
        return response()->json(['data' => $licenses]);
    }

    public function expiring(Request $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $daysThreshold = (int) ($request->query('days', 30));
        $licenses = $this->service->getExpiring($tenantId, $daysThreshold);
        return response()->json(['data' => $licenses]);
    }

    public function update(UpdateLicenseRequest $request, string $licenseId): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $license = $this->service->update($tenantId, $licenseId, $request->validated());
        return response()->json(new LicenseResource($license));
    }

    public function delete(Request $request, string $licenseId): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $this->service->delete($tenantId, $licenseId);
        return response()->json(null, 204);
    }
}
