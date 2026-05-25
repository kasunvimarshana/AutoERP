<?php

declare(strict_types=1);

namespace Modules\Tenant\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Tenant\Application\Contracts\UseCases\SettingGroups\TenantSettingGroupServiceInterface;
use Modules\Tenant\Presentation\Http\Requests\ListTenantSettingGroupRequest;
use Modules\Tenant\Presentation\Http\Requests\UpsertTenantSettingGroupRequest;
use Modules\Tenant\Presentation\Http\Resources\TenantSettingGroupResource;

final class TenantSettingGroupController extends Controller
{
    public function __construct(private readonly TenantSettingGroupServiceInterface $groups)
    {
    }

    public function index(ListTenantSettingGroupRequest $request): JsonResponse
    {
        $result = $this->groups->listByTenant((int) $request->validated('tenant_id'));
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return response()->json(['data' => TenantSettingGroupResource::collection($result->valueOrFail())->resolve()]);
    }

    public function show(int|string $tenantSettingGroup): JsonResponse|TenantSettingGroupResource
    {
        $result = $this->groups->get($tenantSettingGroup);
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new TenantSettingGroupResource($result->valueOrFail());
    }

    public function store(UpsertTenantSettingGroupRequest $request): JsonResponse|TenantSettingGroupResource
    {
        $result = $this->groups->create($request->validated());
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new TenantSettingGroupResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(
        UpsertTenantSettingGroupRequest $request,
        int|string $tenantSettingGroup,
    ): JsonResponse|TenantSettingGroupResource {
        $result = $this->groups->update($tenantSettingGroup, $request->validated());
        if ($result->isFailure()) {
            $status = $result->errorOrFail()->code === 'TENANT_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $result->errorOrFail()->message], $status);
        }

        return new TenantSettingGroupResource($result->valueOrFail());
    }

    public function destroy(int|string $tenantSettingGroup): JsonResponse
    {
        $result = $this->groups->delete($tenantSettingGroup);
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }
}
