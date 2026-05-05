<?php declare(strict_types=1);

namespace Modules\Driver\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Driver\Application\Contracts\ManageCommissionServiceInterface;
use Modules\Driver\Infrastructure\Http\Resources\CommissionResource;

class CommissionController extends Controller
{
    public function __construct(
        private readonly ManageCommissionServiceInterface $service,
    ) {}

    public function getByDriver(Request $request, string $driverId): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $commissions = $this->service->getByDriver($tenantId, $driverId);
        return response()->json(['data' => $commissions]);
    }

    public function getPending(Request $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $commissions = $this->service->getPending($tenantId);
        return response()->json(['data' => $commissions]);
    }
}
