<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\OrganizationUnit\Application\Contracts\UseCases\OrganizationUnitTypes\OrganizationUnitTypeServiceInterface;
use Modules\OrganizationUnit\Presentation\Http\Requests\ListOrganizationUnitTypeRequest;
use Modules\OrganizationUnit\Presentation\Http\Requests\UpsertOrganizationUnitTypeRequest;
use Modules\OrganizationUnit\Presentation\Http\Resources\OrganizationUnitTypeResource;

final class OrganizationUnitTypeController extends Controller
{
    public function __construct(private readonly OrganizationUnitTypeServiceInterface $types)
    {
    }

    public function index(ListOrganizationUnitTypeRequest $request): JsonResponse
    {
        $result = $this->types->listByTenant((int) $request->validated('tenant_id'));
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return response()->json(['data' => OrganizationUnitTypeResource::collection($result->valueOrFail())->resolve()]);
    }

    public function show(int|string $organizationUnitType): JsonResponse|OrganizationUnitTypeResource
    {
        $result = $this->types->get($organizationUnitType);
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new OrganizationUnitTypeResource($result->valueOrFail());
    }

    public function store(UpsertOrganizationUnitTypeRequest $request): JsonResponse|OrganizationUnitTypeResource
    {
        $result = $this->types->create($request->validated());
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new OrganizationUnitTypeResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertOrganizationUnitTypeRequest $request, int|string $organizationUnitType): JsonResponse|OrganizationUnitTypeResource
    {
        $result = $this->types->update($organizationUnitType, $request->validated());
        if ($result->isFailure()) {
            $status = $result->errorOrFail()->code === 'ORGANIZATION_UNIT_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $result->errorOrFail()->message], $status);
        }

        return new OrganizationUnitTypeResource($result->valueOrFail());
    }

    public function destroy(int|string $organizationUnitType): JsonResponse
    {
        $result = $this->types->delete($organizationUnitType);
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }
}