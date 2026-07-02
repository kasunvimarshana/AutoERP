<?php

declare(strict_types=1);

namespace Modules\User\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\User\Http\Requests\Users\ChangeUserStatusRequest;
use Modules\User\Http\Requests\Users\CreateUserRequest;
use Modules\User\Http\Requests\Users\ListUsersRequest;
use Modules\User\Http\Requests\Users\SyncUserOrganizationAccessRequest;
use Modules\User\Http\Requests\Users\SyncUserPermissionsRequest;
use Modules\User\Http\Requests\Users\SyncUserRolesRequest;
use Modules\User\Http\Requests\Users\UpdateUserProfileRequest;
use Modules\User\Http\Requests\Users\VersionedUserActionRequest;
use Modules\User\Http\Resources\UserRecordResource;
use Modules\User\Services\UserAccountService;
use Modules\User\Services\UserOrganizationAccessService;
use Modules\User\Services\UserPermissionAssignmentService;
use Modules\User\Services\UserReadService;
use Modules\User\Services\UserRoleAssignmentService;

final class UserController extends AbstractUserCrudController
{
    public function __construct(
        private readonly UserReadService $queries,
        private readonly UserAccountService $accounts,
        private readonly UserRoleAssignmentService $roles,
        private readonly UserPermissionAssignmentService $permissions,
        private readonly UserOrganizationAccessService $organizationAccess,
    ) {}

    public function index(ListUsersRequest $request): JsonResponse
    {
        return $this->responseForList($this->queries->list($request->validated()));
    }

    public function show(int|string $user): JsonResponse|UserRecordResource
    {
        return $this->responseForShow($this->queries->get($user));
    }

    public function store(CreateUserRequest $request): JsonResponse|UserRecordResource
    {
        return $this->responseForStore($this->accounts->create($request->validated()));
    }

    public function passwordPolicy(): JsonResponse
    {
        return response()->json(['data' => $this->accounts->passwordRequirements()]);
    }

    public function update(UpdateUserProfileRequest $request, int|string $user): JsonResponse|UserRecordResource
    {
        $payload = $request->validated();
        $expectedVersion = (int) $payload['expected_version'];
        unset($payload['expected_version']);

        return $this->responseForUpdate($this->accounts->updateProfile($user, $expectedVersion, $payload));
    }

    public function changeStatus(ChangeUserStatusRequest $request, int|string $user): JsonResponse|UserRecordResource
    {
        $payload = $request->validated();

        return $this->responseForUpdate($this->accounts->changeStatus(
            $user,
            (int) $payload['expected_version'],
            (string) $payload['status'],
            (string) $payload['reason'],
        ));
    }

    public function syncRoles(SyncUserRolesRequest $request, int|string $user): JsonResponse|UserRecordResource
    {
        $payload = $request->validated();

        return $this->responseForUpdate($this->roles->sync(
            $user,
            (int) $payload['expected_version'],
            $payload['role_ids'],
        ));
    }

    public function syncPermissions(SyncUserPermissionsRequest $request, int|string $user): JsonResponse|UserRecordResource
    {
        $payload = $request->validated();

        return $this->responseForUpdate($this->permissions->sync(
            $user,
            (int) $payload['expected_version'],
            $payload['permission_ids'],
        ));
    }

    public function syncOrganizationAccess(
        SyncUserOrganizationAccessRequest $request,
        int|string $user,
    ): JsonResponse|UserRecordResource {
        $payload = $request->validated();

        return $this->responseForUpdate($this->organizationAccess->sync(
            $user,
            (int) $payload['expected_version'],
            $payload['organization_unit_ids'],
            (int) $payload['default_organization_unit_id'],
        ));
    }

    public function destroy(VersionedUserActionRequest $request, int|string $user): JsonResponse
    {
        $payload = $request->validated();

        return $this->responseForDelete($this->accounts->delete(
            $user,
            (int) $payload['expected_version'],
            (string) $payload['reason'],
        ));
    }
}
