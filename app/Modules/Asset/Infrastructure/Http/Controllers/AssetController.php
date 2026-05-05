<?php declare(strict_types=1);

namespace Modules\Asset\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Asset\Application\Contracts\ManageAssetServiceInterface;
use Modules\Asset\Infrastructure\Http\Requests\CreateAssetRequest;
use Modules\Asset\Infrastructure\Http\Requests\UpdateAssetRequest;
use Modules\Asset\Infrastructure\Http\Resources\AssetResource;

class AssetController extends Controller
{
    public function __construct(
        private readonly ManageAssetServiceInterface $service,
    ) {}

    public function create(CreateAssetRequest $request): JsonResponse
    {
        $asset = $this->service->create($request->validated());
        return response()->json(new AssetResource($asset), 201);
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
        $asset = $this->service->find($tenantId, $id);
        return response()->json(new AssetResource($asset));
    }

    public function update(UpdateAssetRequest $request, string $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $asset = $this->service->update($tenantId, $id, $request->validated());
        return response()->json(new AssetResource($asset));
    }

    public function delete(Request $request, string $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $this->service->delete($tenantId, $id);
        return response()->json(null, 204);
    }
}
