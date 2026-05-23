<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\User\Application\DTOs\UserRoleData;
use Modules\User\Application\Services\UserService;
use Modules\User\Domain\Exceptions\UserRecordNotFoundException;
use Modules\User\Presentation\Http\Controllers\Concerns\HandlesUserHttp;
use Modules\User\Presentation\Http\Requests\StoreUserRoleRequest;
use Modules\User\Presentation\Http\Requests\UpdateUserRoleRequest;
use Modules\User\Presentation\Http\Resources\UserRoleResource;

class UserRoleController extends Controller
{
    use HandlesUserHttp;

    public function __construct(private readonly UserService $users)
    {
    }

    public function index(Request $request): mixed
    {
        return UserRoleResource::collection($this->users->listUserRoles(
            $this->filters($request, ['tenant_id', 'organization_unit_id', 'user_id', 'role_id']),
            $this->perPage($request),
        ));
    }

    public function store(StoreUserRoleRequest $request): UserRoleResource
    {
        $record = $this->users->createUserRole(UserRoleData::fromArray($request->validated()));

        return new UserRoleResource($record);
    }

    public function show(int|string $user_role): UserRoleResource|JsonResponse
    {
        try {
            return new UserRoleResource($this->users->findUserRole($user_role));
        } catch (UserRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(UpdateUserRoleRequest $request, int|string $user_role): UserRoleResource|JsonResponse
    {
        try {
            return new UserRoleResource($this->users->updateUserRole($user_role, UserRoleData::fromArray($request->validated())));
        } catch (UserRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $user_role): JsonResponse
    {
        try {
            $this->users->deleteUserRole($user_role);

            return response()->json(null, 204);
        } catch (UserRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }
}
