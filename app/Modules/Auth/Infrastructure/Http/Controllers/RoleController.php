<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Auth\Application\Contracts\RoleServiceInterface;
use Modules\Auth\Infrastructure\Http\Resources\RoleResource;

class RoleController extends Controller
{
    public function __construct(
        private readonly RoleServiceInterface $roleService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (string) $request->user()->tenant_id;
        $roles    = $this->roleService->getAllRoles($tenantId);

        return response()->json(RoleResource::collection(collect($roles)));
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $tenantId = (string) $request->user()->tenant_id;
        $role     = $this->roleService->getRole($tenantId, $id);

        return response()->json(new RoleResource($role));
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = (string) $request->user()->tenant_id;

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'guard'       => 'sometimes|string',
            'permissions' => 'sometimes|array',
        ]);

        $guard = $data['guard'] ?? 'api';

        $request->validate([
            'name' => \Illuminate\Validation\Rule::unique('roles', 'name')
                ->where('tenant_id', $tenantId)
                ->where('guard', $guard)
                ->whereNull('deleted_at'),
        ]);

        $role = $this->roleService->createRole($tenantId, $data);

        return response()->json(new RoleResource($role), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $tenantId = (string) $request->user()->tenant_id;

        $data = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'guard'       => 'sometimes|string',
            'permissions' => 'sometimes|array',
        ]);

        if (isset($data['name'])) {
            $guard = $data['guard'] ?? 'api';

            $request->validate([
                'name' => \Illuminate\Validation\Rule::unique('roles', 'name')
                    ->where('tenant_id', $tenantId)
                    ->where('guard', $guard)
                    ->ignore($id)
                    ->whereNull('deleted_at'),
            ]);
        }

        $role = $this->roleService->updateRole($tenantId, $id, $data);

        return response()->json(new RoleResource($role));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $tenantId = (string) $request->user()->tenant_id;

        $this->roleService->deleteRole($tenantId, $id);

        return response()->json(null, 204);
    }
}
