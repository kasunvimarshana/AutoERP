<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\User\Application\DTOs\UserTenantData;
use Modules\User\Application\Services\UserService;
use Modules\User\Domain\Exceptions\UserRecordNotFoundException;
use Modules\User\Presentation\Http\Controllers\Concerns\HandlesUserHttp;
use Modules\User\Presentation\Http\Requests\StoreUserTenantRequest;
use Modules\User\Presentation\Http\Requests\UpdateUserTenantRequest;
use Modules\User\Presentation\Http\Resources\UserTenantResource;

class UserTenantController extends Controller
{
    use HandlesUserHttp;

    public function __construct(private readonly UserService $users)
    {
    }

    public function index(Request $request): mixed
    {
        return UserTenantResource::collection($this->users->listUserTenants(
            $this->filters($request, ['tenant_id', 'organization_unit_id', 'user_id', 'role_id', 'is_default']),
            $this->perPage($request),
        ));
    }

    public function store(StoreUserTenantRequest $request): UserTenantResource
    {
        $record = $this->users->createUserTenant(UserTenantData::fromArray($request->validated()));

        return new UserTenantResource($record);
    }

    public function show(int|string $user_tenant): UserTenantResource|JsonResponse
    {
        try {
            return new UserTenantResource($this->users->findUserTenant($user_tenant));
        } catch (UserRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(UpdateUserTenantRequest $request, int|string $user_tenant): UserTenantResource|JsonResponse
    {
        try {
            return new UserTenantResource($this->users->updateUserTenant($user_tenant, UserTenantData::fromArray($request->validated())));
        } catch (UserRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $user_tenant): JsonResponse
    {
        try {
            $this->users->deleteUserTenant($user_tenant);

            return response()->json(null, 204);
        } catch (UserRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }
}
