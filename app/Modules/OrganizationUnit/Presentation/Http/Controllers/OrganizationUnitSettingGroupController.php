<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\OrganizationUnit\Application\DTOs\OrganizationUnitSettingGroupData;
use Modules\OrganizationUnit\Application\Services\OrganizationUnitService;
use Modules\OrganizationUnit\Domain\Exceptions\OrganizationUnitRecordNotFoundException;
use Modules\OrganizationUnit\Presentation\Http\Controllers\Concerns\HandlesOrganizationUnitHttp;
use Modules\OrganizationUnit\Presentation\Http\Requests\StoreOrganizationUnitSettingGroupRequest;
use Modules\OrganizationUnit\Presentation\Http\Requests\UpdateOrganizationUnitSettingGroupRequest;
use Modules\OrganizationUnit\Presentation\Http\Resources\OrganizationUnitSettingGroupResource;

class OrganizationUnitSettingGroupController extends Controller
{
    use HandlesOrganizationUnitHttp;

    public function __construct(private readonly OrganizationUnitService $organizationUnits) {}

    public function index(Request $request, int|string $tenant, int|string $unit): mixed
    {
        try {
            return OrganizationUnitSettingGroupResource::collection($this->organizationUnits->listSettingGroups($tenant, $unit, $this->perPage($request)));
        } catch (OrganizationUnitRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function store(StoreOrganizationUnitSettingGroupRequest $request, int|string $tenant, int|string $unit): JsonResponse
    {
        try {
            $group = $this->organizationUnits->createSettingGroup(OrganizationUnitSettingGroupData::fromArray($tenant, $unit, $request->validated()));

            return (new OrganizationUnitSettingGroupResource($group))->response()->setStatusCode(201);
        } catch (OrganizationUnitRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function show(int|string $tenant, int|string $unit, int|string $setting_group): OrganizationUnitSettingGroupResource|JsonResponse
    {
        try {
            return new OrganizationUnitSettingGroupResource($this->organizationUnits->findSettingGroup($tenant, $unit, $setting_group));
        } catch (OrganizationUnitRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(UpdateOrganizationUnitSettingGroupRequest $request, int|string $tenant, int|string $unit, int|string $setting_group): OrganizationUnitSettingGroupResource|JsonResponse
    {
        try {
            return new OrganizationUnitSettingGroupResource($this->organizationUnits->updateSettingGroup($tenant, $unit, $setting_group, OrganizationUnitSettingGroupData::fromArray($tenant, $unit, $request->validated())));
        } catch (OrganizationUnitRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $tenant, int|string $unit, int|string $setting_group): JsonResponse
    {
        try {
            $this->organizationUnits->deleteSettingGroup($tenant, $unit, $setting_group);

            return response()->json(null, 204);
        } catch (OrganizationUnitRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }
}
