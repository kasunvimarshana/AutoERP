<?php

declare(strict_types=1);

namespace Modules\User\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\User\Http\Requests\Roles\ListPermissionsRequest;
use Modules\User\Http\Resources\UserRecordResource;
use Modules\User\Services\PermissionService;

final class PermissionController extends AbstractUserCrudController
{
    public function __construct(private readonly PermissionService $permissions) {}

    public function index(ListPermissionsRequest $request): JsonResponse
    {
        return $this->responseForList($this->permissions->list($request->validated()));
    }

    public function modules(ListPermissionsRequest $request): JsonResponse
    {
        $result = $this->permissions->modules();
        if ($result->isFailure()) {
            return $this->errorResponse($result);
        }

        return response()->json(['data' => $result->valueOrFail()]);
    }

    public function show(int|string $permission): JsonResponse|UserRecordResource
    {
        return $this->responseForShow($this->permissions->get($permission));
    }
}
