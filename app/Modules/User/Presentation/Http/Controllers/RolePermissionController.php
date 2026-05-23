<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\User\Application\DTOs\RolePermissionData;
use Modules\User\Application\Services\UserService;
use Modules\User\Domain\Exceptions\UserRecordNotFoundException;
use Modules\User\Presentation\Http\Controllers\Concerns\HandlesUserHttp;
use Modules\User\Presentation\Http\Requests\StoreRolePermissionRequest;
use Modules\User\Presentation\Http\Requests\UpdateRolePermissionRequest;
use Modules\User\Presentation\Http\Resources\RolePermissionResource;

class RolePermissionController extends Controller
{
    use HandlesUserHttp;

    public function __construct(private readonly UserService $users)
    {
    }

    public function index(Request $request): mixed
    {
        return RolePermissionResource::collection($this->users->listRolePermissions(
            $this->filters($request, ['tenant_id', 'organization_unit_id', 'role_id', 'permission_id']),
            $this->perPage($request),
        ));
    }

    public function store(StoreRolePermissionRequest $request): RolePermissionResource
    {
        $record = $this->users->createRolePermission(RolePermissionData::fromArray($request->validated()));

        return new RolePermissionResource($record);
    }

    public function show(int|string $role_permission): RolePermissionResource|JsonResponse
    {
        try {
            return new RolePermissionResource($this->users->findRolePermission($role_permission));
        } catch (UserRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(UpdateRolePermissionRequest $request, int|string $role_permission): RolePermissionResource|JsonResponse
    {
        try {
            return new RolePermissionResource($this->users->updateRolePermission($role_permission, RolePermissionData::fromArray($request->validated())));
        } catch (UserRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $role_permission): JsonResponse
    {
        try {
            $this->users->deleteRolePermission($role_permission);

            return response()->json(null, 204);
        } catch (UserRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }
}
