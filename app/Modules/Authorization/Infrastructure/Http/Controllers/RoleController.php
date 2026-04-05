<?php
declare(strict_types=1);
namespace Modules\Authorization\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Authorization\Application\Contracts\RoleServiceInterface;
use Modules\Authorization\Infrastructure\Http\Resources\RoleResource;

class RoleController extends Controller
{
    public function __construct(private readonly RoleServiceInterface $service) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->get('tenant_id', 0);
        $result = $this->service->findByTenant(
            $tenantId,
            (int) $request->get('per_page', 15),
            (int) $request->get('page', 1)
        );
        return response()->json($result);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(new RoleResource($this->service->findById($id)));
    }

    public function store(Request $request): JsonResponse
    {
        $role = $this->service->create($request->all());
        return response()->json(new RoleResource($role), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $role = $this->service->update($id, $request->all());
        return response()->json(new RoleResource($role));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);
        return response()->json(null, 204);
    }

    public function syncPermissions(Request $request, int $id): JsonResponse
    {
        $this->service->syncPermissions($id, $request->get('permission_ids', []));
        return response()->json(['message' => 'Permissions synced.']);
    }

    public function getPermissions(int $id): JsonResponse
    {
        return response()->json($this->service->getPermissions($id));
    }
}
