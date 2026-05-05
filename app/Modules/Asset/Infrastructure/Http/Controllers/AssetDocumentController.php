<?php declare(strict_types=1);

namespace Modules\Asset\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Asset\Application\Contracts\ManageAssetDocumentServiceInterface;
use Modules\Asset\Infrastructure\Http\Requests\CreateAssetDocumentRequest;
use Modules\Asset\Infrastructure\Http\Requests\UpdateAssetDocumentRequest;
use Modules\Asset\Infrastructure\Http\Resources\AssetDocumentResource;

class AssetDocumentController extends Controller
{
    public function __construct(
        private readonly ManageAssetDocumentServiceInterface $service,
    ) {}

    public function create(CreateAssetDocumentRequest $request): JsonResponse
    {
        $document = $this->service->create($request->validated());
        return response()->json(new AssetDocumentResource($document), 201);
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
        $document = $this->service->find($tenantId, $id);
        return response()->json(new AssetDocumentResource($document));
    }

    public function update(UpdateAssetDocumentRequest $request, string $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $document = $this->service->update($tenantId, $id, $request->validated());
        return response()->json(new AssetDocumentResource($document));
    }

    public function delete(Request $request, string $id): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $this->service->delete($tenantId, $id);
        return response()->json(null, 204);
    }

    public function expiring(Request $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID');
        $daysThreshold = (int) ($request->query('days', 30));
        $documents = $this->service->getExpiring($tenantId, $daysThreshold);
        return response()->json(['data' => $documents]);
    }
}
