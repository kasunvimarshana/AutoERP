<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\OrganizationUnit\Http\Requests\ListOrganizationUnitSettingGroupRequest;
use Modules\OrganizationUnit\Http\Requests\UpsertOrganizationUnitSettingGroupRequest;
use Modules\OrganizationUnit\Http\Resources\OrganizationUnitSettingGroupResource;
use Modules\OrganizationUnit\Services\OrganizationUnitSettingGroups\OrganizationUnitSettingGroupService;

final class OrganizationUnitSettingGroupController extends Controller
{
    public function __construct(private readonly OrganizationUnitSettingGroupService $groups) {}

    public function index(ListOrganizationUnitSettingGroupRequest $request): JsonResponse
    {
        $result = $this->groups->listByTenant((int) $request->validated('tenant_id'));
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return response()->json(['data' => OrganizationUnitSettingGroupResource::collection($result->valueOrFail())->resolve()]);
    }

    public function show(int|string $organizationUnitSettingGroup): JsonResponse|OrganizationUnitSettingGroupResource
    {
        $result = $this->groups->get($organizationUnitSettingGroup);
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new OrganizationUnitSettingGroupResource($result->valueOrFail());
    }

    public function store(UpsertOrganizationUnitSettingGroupRequest $request): JsonResponse|OrganizationUnitSettingGroupResource
    {
        $result = $this->groups->create($request->validated());
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new OrganizationUnitSettingGroupResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertOrganizationUnitSettingGroupRequest $request, int|string $organizationUnitSettingGroup): JsonResponse|OrganizationUnitSettingGroupResource
    {
        $result = $this->groups->update($organizationUnitSettingGroup, $request->validated());
        if ($result->isFailure()) {
            $status = $result->errorOrFail()->code === 'ORGANIZATION_UNIT_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $result->errorOrFail()->message], $status);
        }

        return new OrganizationUnitSettingGroupResource($result->valueOrFail());
    }

    public function destroy(int|string $organizationUnitSettingGroup): JsonResponse
    {
        $result = $this->groups->delete($organizationUnitSettingGroup);
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }
}
