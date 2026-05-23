<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\User\Application\DTOs\UserPermissionData;
use Modules\User\Application\Services\UserService;
use Modules\User\Domain\Exceptions\UserRecordNotFoundException;
use Modules\User\Presentation\Http\Controllers\Concerns\HandlesUserHttp;
use Modules\User\Presentation\Http\Requests\StoreUserPermissionRequest;
use Modules\User\Presentation\Http\Requests\UpdateUserPermissionRequest;
use Modules\User\Presentation\Http\Resources\UserPermissionResource;

class UserPermissionController extends Controller
{
    use HandlesUserHttp;

    public function __construct(private readonly UserService $users)
    {
    }

    public function index(Request $request): mixed
    {
        return UserPermissionResource::collection($this->users->listUserPermissions(
            $this->filters($request, ['tenant_id', 'organization_unit_id', 'user_id', 'permission_id']),
            $this->perPage($request),
        ));
    }

    public function store(StoreUserPermissionRequest $request): UserPermissionResource
    {
        $record = $this->users->createUserPermission(UserPermissionData::fromArray($request->validated()));

        return new UserPermissionResource($record);
    }

    public function show(int|string $user_permission): UserPermissionResource|JsonResponse
    {
        try {
            return new UserPermissionResource($this->users->findUserPermission($user_permission));
        } catch (UserRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(UpdateUserPermissionRequest $request, int|string $user_permission): UserPermissionResource|JsonResponse
    {
        try {
            return new UserPermissionResource($this->users->updateUserPermission($user_permission, UserPermissionData::fromArray($request->validated())));
        } catch (UserRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $user_permission): JsonResponse
    {
        try {
            $this->users->deleteUserPermission($user_permission);

            return response()->json(null, 204);
        } catch (UserRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }
}
