<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\User\Application\UseCases\UserService;
use Modules\User\Domain\Constants\UserErrorCode;
use Modules\User\Presentation\Http\Requests\AssignUserToOrganizationUnitRequest;
use Modules\User\Presentation\Http\Requests\ListUserEntityRequest;
use Modules\User\Presentation\Http\Requests\ResolveUserByIdentityRequest;
use Modules\User\Presentation\Http\Requests\UpsertUserRequest;
use Modules\User\Presentation\Http\Resources\UserRecordResource;

final class UserController extends AbstractUserCrudController
{
    public function __construct(private readonly UserService $service) {}

    public function index(ListUserEntityRequest $request): JsonResponse
    {
        return $this->responseForList($this->service->list($request->validated()));
    }

    public function show(int|string $user): JsonResponse|UserRecordResource
    {
        return $this->responseForShow($this->service->get($user));
    }

    public function store(UpsertUserRequest $request): JsonResponse|UserRecordResource
    {
        return $this->responseForStore($this->service->create($request->validated()));
    }

    public function update(UpsertUserRequest $request, int|string $user): JsonResponse|UserRecordResource
    {
        return $this->responseForUpdate($this->service->update($user, $request->validated()));
    }

    public function destroy(int|string $user): JsonResponse
    {
        return $this->responseForDelete($this->service->delete($user));
    }

    public function activate(int|string $user): JsonResponse|UserRecordResource
    {
        return $this->responseForUpdate($this->service->activate($user));
    }

    public function deactivate(int|string $user): JsonResponse|UserRecordResource
    {
        return $this->responseForUpdate($this->service->deactivate($user));
    }

    public function suspend(int|string $user): JsonResponse|UserRecordResource
    {
        return $this->responseForUpdate($this->service->suspend($user));
    }

    public function assignOrganizationUnit(
        AssignUserToOrganizationUnitRequest $request,
        int|string $user,
    ): JsonResponse|UserRecordResource {
        return $this->responseForStore($this->service->assignUserToOrganizationUnit($user, $request->validated()));
    }

    public function removeOrganizationUnit(
        int|string $user,
        int|string $organizationUnit,
    ): JsonResponse {
        return $this->responseForDelete(
            $this->service->removeUserFromOrganizationUnit($user, $organizationUnit),
            UserErrorCode::ASSIGNMENT_NOT_FOUND,
        );
    }

    public function resolveByIdentity(
        ResolveUserByIdentityRequest $request,
    ): JsonResponse|UserRecordResource {
        $payload = $request->validated();

        return $this->responseForShow(
            $this->service->resolveByIdentity(
                (string) $payload['provider_key'],
                (string) $payload['provider_user_key'],
            ),
        );
    }
}
