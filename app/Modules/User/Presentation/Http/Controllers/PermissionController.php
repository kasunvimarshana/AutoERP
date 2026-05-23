<?php

declare(strict_types=1);

namespace Modules\User\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use InvalidArgumentException;
use Modules\User\Application\DTOs\PermissionData;
use Modules\User\Application\Services\UserService;
use Modules\User\Domain\Exceptions\UserRecordNotFoundException;
use Modules\User\Presentation\Http\Controllers\Concerns\HandlesUserHttp;
use Modules\User\Presentation\Http\Requests\StorePermissionRequest;
use Modules\User\Presentation\Http\Requests\UpdatePermissionRequest;
use Modules\User\Presentation\Http\Resources\PermissionResource;

class PermissionController extends Controller
{
    use HandlesUserHttp;

    public function __construct(private readonly UserService $users)
    {
    }

    public function index(Request $request): mixed
    {
        return PermissionResource::collection($this->users->listPermissions(
            $this->filters($request, ['tenant_id', 'organization_unit_id', 'name', 'guard_name', 'module']),
            $this->perPage($request),
        ));
    }

    public function store(StorePermissionRequest $request): PermissionResource|JsonResponse
    {
        try {
            $record = $this->users->createPermission(PermissionData::fromArray($request->validated()));

            return (new PermissionResource($record))->response()->setStatusCode(201);
        } catch (InvalidArgumentException $exception) {
            return $this->invalid($exception);
        }
    }

    public function show(int|string $permission): PermissionResource|JsonResponse
    {
        try {
            return new PermissionResource($this->users->findPermission($permission));
        } catch (UserRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(UpdatePermissionRequest $request, int|string $permission): PermissionResource|JsonResponse
    {
        try {
            return new PermissionResource($this->users->updatePermission($permission, PermissionData::fromArray($request->validated())));
        } catch (UserRecordNotFoundException $exception) {
            return $this->notFound($exception);
        } catch (InvalidArgumentException $exception) {
            return $this->invalid($exception);
        }
    }

    public function destroy(int|string $permission): JsonResponse
    {
        try {
            $this->users->deletePermission($permission);

            return response()->json(null, 204);
        } catch (UserRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }
}
