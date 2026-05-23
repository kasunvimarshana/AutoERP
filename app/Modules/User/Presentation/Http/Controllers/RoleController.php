<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use InvalidArgumentException;
use Modules\User\Application\DTOs\RoleData;
use Modules\User\Application\Services\UserService;
use Modules\User\Domain\Exceptions\UserRecordNotFoundException;
use Modules\User\Presentation\Http\Controllers\Concerns\HandlesUserHttp;
use Modules\User\Presentation\Http\Requests\StoreRoleRequest;
use Modules\User\Presentation\Http\Requests\UpdateRoleRequest;
use Modules\User\Presentation\Http\Resources\RoleResource;

class RoleController extends Controller
{
    use HandlesUserHttp;

    public function __construct(private readonly UserService $users)
    {
    }

    public function index(Request $request): mixed
    {
        return RoleResource::collection($this->users->listRoles(
            $this->filters($request, ['tenant_id', 'organization_unit_id', 'name', 'guard_name']),
            $this->perPage($request),
        ));
    }

    public function store(StoreRoleRequest $request): RoleResource|JsonResponse
    {
        try {
            $record = $this->users->createRole(RoleData::fromArray($request->validated()));

            return (new RoleResource($record))->response()->setStatusCode(201);
        } catch (InvalidArgumentException $exception) {
            return $this->invalid($exception);
        }
    }

    public function show(int|string $role): RoleResource|JsonResponse
    {
        try {
            return new RoleResource($this->users->findRole($role));
        } catch (UserRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(UpdateRoleRequest $request, int|string $role): RoleResource|JsonResponse
    {
        try {
            return new RoleResource($this->users->updateRole($role, RoleData::fromArray($request->validated())));
        } catch (UserRecordNotFoundException $exception) {
            return $this->notFound($exception);
        } catch (InvalidArgumentException $exception) {
            return $this->invalid($exception);
        }
    }

    public function destroy(int|string $role): JsonResponse
    {
        try {
            $this->users->deleteRole($role);

            return response()->json(null, 204);
        } catch (UserRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }
}
