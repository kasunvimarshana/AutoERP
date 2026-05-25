<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\OrganizationUnit\Application\Contracts\UseCases\OrganizationUnitSettings\OrganizationUnitSettingServiceInterface;
use Modules\OrganizationUnit\Presentation\Http\Requests\ListOrganizationUnitSettingRequest;
use Modules\OrganizationUnit\Presentation\Http\Requests\UpsertOrganizationUnitSettingRequest;
use Modules\OrganizationUnit\Presentation\Http\Resources\OrganizationUnitSettingResource;

final class OrganizationUnitSettingController extends Controller
{
    public function __construct(private readonly OrganizationUnitSettingServiceInterface $settings)
    {
    }

    public function index(ListOrganizationUnitSettingRequest $request): JsonResponse
    {
        $result = $this->settings->listByTenant((int) $request->validated('tenant_id'));
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return response()->json(['data' => OrganizationUnitSettingResource::collection($result->valueOrFail())->resolve()]);
    }

    public function show(int|string $organizationUnitSetting): JsonResponse|OrganizationUnitSettingResource
    {
        $result = $this->settings->get($organizationUnitSetting);
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new OrganizationUnitSettingResource($result->valueOrFail());
    }

    public function store(UpsertOrganizationUnitSettingRequest $request): JsonResponse|OrganizationUnitSettingResource
    {
        $result = $this->settings->create($request->validated());
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new OrganizationUnitSettingResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertOrganizationUnitSettingRequest $request, int|string $organizationUnitSetting): JsonResponse|OrganizationUnitSettingResource
    {
        $result = $this->settings->update($organizationUnitSetting, $request->validated());
        if ($result->isFailure()) {
            $status = $result->errorOrFail()->code === 'ORGANIZATION_UNIT_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $result->errorOrFail()->message], $status);
        }

        return new OrganizationUnitSettingResource($result->valueOrFail());
    }

    public function destroy(int|string $organizationUnitSetting): JsonResponse
    {
        $result = $this->settings->delete($organizationUnitSetting);
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }
}