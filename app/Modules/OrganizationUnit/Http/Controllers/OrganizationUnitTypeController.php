<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\OrganizationUnit\Constants\OrganizationUnitPermission;
use Modules\OrganizationUnit\Http\Requests\ListOrganizationUnitTypeRequest;
use Modules\OrganizationUnit\Http\Requests\OrganizationUnitVersionRequest;
use Modules\OrganizationUnit\Http\Requests\UpsertOrganizationUnitTypeRequest;
use Modules\OrganizationUnit\Http\Resources\OrganizationUnitTypeResource;
use Modules\OrganizationUnit\Http\Responses\OrganizationUnitApiResponder;
use Modules\OrganizationUnit\Services\Authorization\OrganizationUnitAuthorizationService;
use Modules\OrganizationUnit\Services\OrganizationUnitTypes\OrganizationUnitTypeService;

final class OrganizationUnitTypeController extends Controller
{
    public function __construct(
        private readonly OrganizationUnitTypeService $types,
        private readonly OrganizationUnitAuthorizationService $authorization,
    ) {}

    public function index(ListOrganizationUnitTypeRequest $request): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), OrganizationUnitPermission::TYPES_VIEW);
        $result = $this->types->list();
        return $result->isFailure()
            ? OrganizationUnitApiResponder::error($result->errorOrFail())
            : response()->json(['data' => OrganizationUnitTypeResource::collection($result->valueOrFail())->resolve($request)]);
    }

    public function show(ListOrganizationUnitTypeRequest $request, int|string $organizationUnitType): JsonResponse|OrganizationUnitTypeResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), OrganizationUnitPermission::TYPES_VIEW);
        $result = $this->types->get($organizationUnitType);
        return $result->isFailure()
            ? OrganizationUnitApiResponder::error($result->errorOrFail())
            : new OrganizationUnitTypeResource($result->valueOrFail());
    }

    public function store(UpsertOrganizationUnitTypeRequest $request): JsonResponse|OrganizationUnitTypeResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), OrganizationUnitPermission::TYPES_MANAGE);
        $result = $this->types->create($request->validated());
        return $result->isFailure()
            ? OrganizationUnitApiResponder::error($result->errorOrFail())
            : (new OrganizationUnitTypeResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertOrganizationUnitTypeRequest $request, int|string $organizationUnitType): JsonResponse|OrganizationUnitTypeResource
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), OrganizationUnitPermission::TYPES_MANAGE);
        $result = $this->types->update($organizationUnitType, $request->validated());
        return $result->isFailure()
            ? OrganizationUnitApiResponder::error($result->errorOrFail())
            : new OrganizationUnitTypeResource($result->valueOrFail());
    }

    public function destroy(OrganizationUnitVersionRequest $request, int|string $organizationUnitType): JsonResponse
    {
        $this->authorization->assert($request->currentUserId(), $request->tenantId(), OrganizationUnitPermission::TYPES_MANAGE);
        $result = $this->types->delete($organizationUnitType, (int) $request->validated('expected_version'));
        return $result->isFailure()
            ? OrganizationUnitApiResponder::error($result->errorOrFail())
            : response()->json(null, 204);
    }
}
