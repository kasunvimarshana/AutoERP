<?php
namespace Modules\Authorization\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Authorization\Application\Contracts\AssignUserRoleServiceInterface;
use Modules\Authorization\Application\DTOs\AssignUserRoleData;

class UserRoleController extends Controller
{
    public function __construct(
        private readonly AssignUserRoleServiceInterface $assignService,
    ) {}

    public function assign(Request $request, int $userId): JsonResponse
    {
        $request->validate([
            'role_id'   => ['required', 'integer'],
            'tenant_id' => ['required', 'integer'],
        ]);
        $userRole = $this->assignService->execute(new AssignUserRoleData(
            tenantId: $request->integer('tenant_id'),
            userId:   $userId,
            roleId:   $request->integer('role_id'),
        ));
        return response()->json(['user_id' => $userRole->userId, 'role_id' => $userRole->roleId], 201);
    }
}
