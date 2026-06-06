<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\OrganizationUnit\Http\Requests\AssignUserToOrganizationUnitRequest;
use Modules\OrganizationUnit\Http\Requests\ListOrganizationUnitRequest;
use Modules\OrganizationUnit\Http\Requests\UpsertOrganizationUnitRequest;
use Modules\OrganizationUnit\Http\Resources\OrganizationUnitResource;
use Modules\OrganizationUnit\Services\OrganizationUnits as OrganizationUnitUseCases;
use Modules\OrganizationUnit\Services\OrganizationUnits\OrganizationUnitService;
use Modules\OrganizationUnit\Services\OrganizationUnits\ResolveOrganizationUnitService;

final class OrganizationUnitController extends Controller
{
    public function __construct(
        private readonly OrganizationUnitService $units,
        private readonly ResolveOrganizationUnitService $resolveOrganizationUnit,
        private readonly OrganizationUnitUseCases\AssignUserToOrganizationUnitService $assignUserToUnit,
    ) {}

    public function resolve(): JsonResponse|OrganizationUnitResource
    {
        $result = $this->resolveOrganizationUnit->execute();
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new OrganizationUnitResource($result->valueOrFail());
    }

    public function index(ListOrganizationUnitRequest $request): JsonResponse
    {
        $result = $this->units->listByTenant((int) $request->validated('tenant_id'));
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return response()->json(['data' => OrganizationUnitResource::collection($result->valueOrFail())->resolve()]);
    }

    public function show(int|string $organizationUnit): JsonResponse|OrganizationUnitResource
    {
        $result = $this->units->get($organizationUnit);
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new OrganizationUnitResource($result->valueOrFail());
    }

    public function store(UpsertOrganizationUnitRequest $request): JsonResponse|OrganizationUnitResource
    {
        $result = $this->units->create($request->validated());
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new OrganizationUnitResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(
        UpsertOrganizationUnitRequest $request,
        int|string $organizationUnit,
    ): JsonResponse|OrganizationUnitResource {
        $result = $this->units->update($organizationUnit, $request->validated());
        if ($result->isFailure()) {
            $status = $result->errorOrFail()->code === 'ORGANIZATION_UNIT_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $result->errorOrFail()->message], $status);
        }

        return new OrganizationUnitResource($result->valueOrFail());
    }

    public function activate(int|string $organizationUnit): JsonResponse|OrganizationUnitResource
    {
        $result = $this->units->activate($organizationUnit);
        if ($result->isFailure()) {
            $status = $result->errorOrFail()->code === 'ORGANIZATION_UNIT_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $result->errorOrFail()->message], $status);
        }

        return new OrganizationUnitResource($result->valueOrFail());
    }

    public function deactivate(int|string $organizationUnit): JsonResponse|OrganizationUnitResource
    {
        $result = $this->units->deactivate($organizationUnit);
        if ($result->isFailure()) {
            $status = $result->errorOrFail()->code === 'ORGANIZATION_UNIT_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $result->errorOrFail()->message], $status);
        }

        return new OrganizationUnitResource($result->valueOrFail());
    }

    public function assignUser(
        AssignUserToOrganizationUnitRequest $request,
        int|string $organizationUnit,
    ): JsonResponse {
        $result = $this->assignUserToUnit->execute($organizationUnit, $request->validated());
        if ($result->isFailure()) {
            $status = $result->errorOrFail()->code === 'ORGANIZATION_UNIT_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $result->errorOrFail()->message], $status);
        }

        return response()->json(['data' => $result->valueOrFail()], 201);
    }

    public function destroy(int|string $organizationUnit): JsonResponse
    {
        $result = $this->units->delete($organizationUnit);
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }
}
