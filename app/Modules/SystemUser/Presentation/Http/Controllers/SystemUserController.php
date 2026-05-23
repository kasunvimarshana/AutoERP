<?php

declare(strict_types=1);

namespace Modules\SystemUser\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\SystemUser\Application\DTOs\SystemUserData;
use Modules\SystemUser\Application\Services\SystemUserService;
use Modules\SystemUser\Domain\Exceptions\SystemUserRecordNotFoundException;
use Modules\SystemUser\Presentation\Http\Controllers\Concerns\HandlesSystemUserHttp;
use Modules\SystemUser\Presentation\Http\Requests\StoreSystemUserRequest;
use Modules\SystemUser\Presentation\Http\Requests\UpdateSystemUserRequest;
use Modules\SystemUser\Presentation\Http\Resources\SystemUserResource;

class SystemUserController extends Controller
{
    use HandlesSystemUserHttp;

    public function __construct(private readonly SystemUserService $systemUsers)
    {
    }

    public function index(Request $request, int|string $tenant): mixed
    {
        try {
            return SystemUserResource::collection($this->systemUsers->listSystemUsers(
                $tenant,
                $this->filters($request, ['organization_unit_id', 'user_id', 'status', 'code', 'registration_number']),
                $this->perPage($request),
            ));
        } catch (SystemUserRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function store(StoreSystemUserRequest $request, int|string $tenant): JsonResponse
    {
        try {
            $systemUser = $this->systemUsers->createSystemUser(SystemUserData::fromArray($tenant, $request->validated()));

            return (new SystemUserResource($systemUser))->response()->setStatusCode(201);
        } catch (SystemUserRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function show(int|string $tenant, int|string $systemUser): SystemUserResource|JsonResponse
    {
        try {
            return new SystemUserResource($this->systemUsers->findSystemUser($tenant, $systemUser));
        } catch (SystemUserRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(
        UpdateSystemUserRequest $request,
        int|string $tenant,
        int|string $systemUser,
    ): SystemUserResource|JsonResponse {
        try {
            return new SystemUserResource(
                $this->systemUsers->updateSystemUser(
                    $tenant,
                    $systemUser,
                    SystemUserData::fromArray($tenant, $request->validated()),
                ),
            );
        } catch (SystemUserRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $tenant, int|string $systemUser): JsonResponse
    {
        try {
            $this->systemUsers->deleteSystemUser($tenant, $systemUser);

            return response()->json(null, 204);
        } catch (SystemUserRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }
}
