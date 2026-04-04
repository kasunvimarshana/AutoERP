<?php
namespace Modules\Authorization\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Authorization\Application\Contracts\CreateRoleServiceInterface;
use Modules\Authorization\Application\Contracts\DeleteRoleServiceInterface;
use Modules\Authorization\Application\Contracts\SyncRolePermissionsServiceInterface;
use Modules\Authorization\Application\DTOs\RoleData;
use Modules\Authorization\Application\DTOs\SyncPermissionsData;
use Modules\Authorization\Domain\RepositoryInterfaces\RoleRepositoryInterface;
use Modules\Authorization\Infrastructure\Http\Resources\RoleResource;

class RoleController extends Controller
{
    public function __construct(
        private readonly RoleRepositoryInterface $repository,
        private readonly CreateRoleServiceInterface $createService,
        private readonly DeleteRoleServiceInterface $deleteService,
        private readonly SyncRolePermissionsServiceInterface $syncService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->header('X-Tenant-ID', 1);
        return response()->json($this->repository->findAll($tenantId));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'      => ['required', 'string', 'max:100'],
            'tenant_id' => ['required', 'integer'],
        ]);
        $role = $this->createService->execute(new RoleData(
            tenantId:    $request->integer('tenant_id'),
            name:        $request->string('name')->value(),
            description: $request->input('description'),
        ));
        return response()->json(new RoleResource($role), 201);
    }

    public function show(int $id): JsonResponse
    {
        $role = $this->repository->findById($id);
        if (!$role) return response()->json(['message' => 'Not found'], 404);
        return response()->json(new RoleResource($role));
    }

    public function destroy(int $id): JsonResponse
    {
        $role = $this->repository->findById($id);
        if (!$role) return response()->json(['message' => 'Not found'], 404);
        $this->deleteService->execute($role);
        return response()->json(null, 204);
    }

    public function syncPermissions(Request $request, int $id): JsonResponse
    {
        $request->validate(['permission_ids' => ['required', 'array']]);
        $this->syncService->execute(new SyncPermissionsData(
            roleId:        $id,
            permissionIds: $request->input('permission_ids', []),
        ));
        return response()->json(['message' => 'Permissions synced.']);
    }
}
