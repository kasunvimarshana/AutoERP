<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\OrganizationUnit\Application\DTOs\OrganizationUnitSettingData;
use Modules\OrganizationUnit\Application\Services\OrganizationUnitService;
use Modules\OrganizationUnit\Domain\Exceptions\OrganizationUnitRecordNotFoundException;
use Modules\OrganizationUnit\Presentation\Http\Controllers\Concerns\HandlesOrganizationUnitHttp;
use Modules\OrganizationUnit\Presentation\Http\Requests\StoreOrganizationUnitSettingRequest;
use Modules\OrganizationUnit\Presentation\Http\Requests\UpdateOrganizationUnitSettingRequest;
use Modules\OrganizationUnit\Presentation\Http\Resources\OrganizationUnitSettingResource;

class OrganizationUnitSettingController extends Controller
{
    use HandlesOrganizationUnitHttp;

    public function __construct(private readonly OrganizationUnitService $organizationUnits) {}

    public function index(Request $request, int|string $tenant, int|string $unit): mixed
    {
        try {
            return OrganizationUnitSettingResource::collection($this->organizationUnits->listSettings($tenant, $unit, $this->perPage($request)));
        } catch (OrganizationUnitRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function store(StoreOrganizationUnitSettingRequest $request, int|string $tenant, int|string $unit): JsonResponse
    {
        try {
            $setting = $this->organizationUnits->createSetting(OrganizationUnitSettingData::fromArray($tenant, $unit, $request->validated()));

            return (new OrganizationUnitSettingResource($setting))->response()->setStatusCode(201);
        } catch (OrganizationUnitRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function show(int|string $tenant, int|string $unit, int|string $setting): OrganizationUnitSettingResource|JsonResponse
    {
        try {
            return new OrganizationUnitSettingResource($this->organizationUnits->findSetting($tenant, $unit, $setting));
        } catch (OrganizationUnitRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(UpdateOrganizationUnitSettingRequest $request, int|string $tenant, int|string $unit, int|string $setting): OrganizationUnitSettingResource|JsonResponse
    {
        try {
            return new OrganizationUnitSettingResource($this->organizationUnits->updateSetting($tenant, $unit, $setting, OrganizationUnitSettingData::fromArray($tenant, $unit, $request->validated())));
        } catch (OrganizationUnitRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $tenant, int|string $unit, int|string $setting): JsonResponse
    {
        try {
            $this->organizationUnits->deleteSetting($tenant, $unit, $setting);

            return response()->json(null, 204);
        } catch (OrganizationUnitRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }
}
