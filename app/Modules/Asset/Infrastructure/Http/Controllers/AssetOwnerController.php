<?php declare(strict_types=1);

namespace Modules\Asset\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Asset\Application\Contracts\ManageAssetOwnerServiceInterface;
use Modules\Asset\Infrastructure\Http\Requests\CreateAssetOwnerRequest;
use Modules\Asset\Infrastructure\Http\Requests\UpdateAssetOwnerRequest;
use Modules\Asset\Infrastructure\Http\Resources\AssetOwnerResource;

class AssetOwnerController extends Controller
{
    public function __construct(
        private readonly ManageAssetOwnerServiceInterface $service,
    ) {}

    public function create(CreateAssetOwnerRequest $request): JsonResponse
    {
        $owner = $this->service->create($request->validated());
        return response()->json(new AssetOwnerResource($owner), 201);
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
        $owner = $this->service->find($tenantId, $id);
        return response()->json(new AssetOwnerResource($owner));
    }

    public function update(UpdateAssetOwnerRequest $request, string $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $owner = $this->service->update($tenantId, $id, $request->validated());
        return response()->json(new AssetOwnerResource($owner));
    }

    public function delete(Request $request, string $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $this->service->delete($tenantId, $id);
        return response()->json(null, 204);
    }
}
