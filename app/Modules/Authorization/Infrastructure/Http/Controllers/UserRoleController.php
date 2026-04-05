<?php
declare(strict_types=1);
namespace Modules\Authorization\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Authorization\Application\Contracts\UserRoleServiceInterface;

class UserRoleController extends Controller
{
    public function __construct(private readonly UserRoleServiceInterface $service) {}

    public function getUserRoles(int $userId): JsonResponse
    {
        return response()->json($this->service->getUserRoles($userId));
    }

    public function assignRole(Request $request, int $userId): JsonResponse
    {
        $this->service->assignRole($userId, (int) $request->get('role_id'));
        return response()->json(['message' => 'Role assigned.']);
    }

    public function removeRole(int $userId, int $roleId): JsonResponse
    {
        $this->service->removeRole($userId, $roleId);
        return response()->json(['message' => 'Role removed.']);
    }

    public function syncRoles(Request $request, int $userId): JsonResponse
    {
        $this->service->syncRoles($userId, $request->get('role_ids', []));
        return response()->json(['message' => 'Roles synced.']);
    }

    public function userHasPermission(int $userId, string $permissionSlug): JsonResponse
    {
        return response()->json(['has_permission' => $this->service->userHasPermission($userId, $permissionSlug)]);
    }

    public function userHasRole(int $userId, string $roleSlug): JsonResponse
    {
        return response()->json(['has_role' => $this->service->userHasRole($userId, $roleSlug)]);
    }
}
