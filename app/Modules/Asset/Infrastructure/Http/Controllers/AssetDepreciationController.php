<?php declare(strict_types=1);

namespace Modules\Asset\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Asset\Application\Contracts\ManageAssetDepreciationServiceInterface;
use Modules\Asset\Infrastructure\Http\Resources\AssetDepreciationResource;

class AssetDepreciationController extends Controller
{
    public function __construct(
        private readonly ManageAssetDepreciationServiceInterface $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $result = $this->service->list($tenantId, (int) ($request->query('per_page', 15)), (int) ($request->query('page', 1)));
        return response()->json($result);
    }

    public function pending(Request $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $records = $this->service->getPending($tenantId);
        return response()->json(['data' => $records]);
    }

    public function post(Request $request, string $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $depreciation = $this->service->post($tenantId, $id);
        return response()->json(new AssetDepreciationResource($depreciation));
    }
}
