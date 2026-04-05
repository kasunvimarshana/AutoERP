<?php
declare(strict_types=1);
namespace Modules\Configuration\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Configuration\Application\Contracts\OrgUnitServiceInterface;
use Modules\Configuration\Infrastructure\Http\Resources\OrgUnitResource;

class OrgUnitController extends Controller
{
    public function __construct(private readonly OrgUnitServiceInterface $service) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->get('tenant_id', 0);
        return response()->json($this->service->findByTenant($tenantId));
    }

    public function tree(Request $request): JsonResponse
    {
        $tenantId = (int) $request->get('tenant_id', 0);
        return response()->json($this->service->getTree($tenantId));
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(new OrgUnitResource($this->service->findById($id)));
    }

    public function store(Request $request): JsonResponse
    {
        $unit = $this->service->create($request->all());
        return response()->json(new OrgUnitResource($unit), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $unit = $this->service->update($id, $request->all());
        return response()->json(new OrgUnitResource($unit));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);
        return response()->json(null, 204);
    }
}
