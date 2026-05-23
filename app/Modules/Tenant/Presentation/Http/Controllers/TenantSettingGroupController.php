<?php

declare(strict_types=1);

namespace Modules\Tenant\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Tenant\Application\DTOs\TenantSettingGroupData;
use Modules\Tenant\Application\Services\TenantService;
use Modules\Tenant\Domain\Exceptions\TenantRecordNotFoundException;
use Modules\Tenant\Presentation\Http\Controllers\Concerns\HandlesTenantHttp;
use Modules\Tenant\Presentation\Http\Requests\StoreTenantSettingGroupRequest;
use Modules\Tenant\Presentation\Http\Requests\UpdateTenantSettingGroupRequest;
use Modules\Tenant\Presentation\Http\Resources\TenantSettingGroupResource;

class TenantSettingGroupController extends Controller
{
    use HandlesTenantHttp;

    public function __construct(private readonly TenantService $tenants) {}

    public function index(Request $request, int|string $tenant): mixed
    {
        try {
            return TenantSettingGroupResource::collection($this->tenants->listSettingGroups($tenant, $this->perPage($request)));
        } catch (TenantRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function store(StoreTenantSettingGroupRequest $request, int|string $tenant): JsonResponse
    {
        try {
            $group = $this->tenants->createSettingGroup(TenantSettingGroupData::fromArray($tenant, $request->validated()));

            return (new TenantSettingGroupResource($group))->response()->setStatusCode(201);
        } catch (TenantRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function show(int|string $tenant, int|string $setting_group): TenantSettingGroupResource|JsonResponse
    {
        try {
            return new TenantSettingGroupResource($this->tenants->findSettingGroup($tenant, $setting_group));
        } catch (TenantRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(UpdateTenantSettingGroupRequest $request, int|string $tenant, int|string $setting_group): TenantSettingGroupResource|JsonResponse
    {
        try {
            return new TenantSettingGroupResource($this->tenants->updateSettingGroup($tenant, $setting_group, TenantSettingGroupData::fromArray($tenant, $request->validated())));
        } catch (TenantRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $tenant, int|string $setting_group): JsonResponse
    {
        try {
            $this->tenants->deleteSettingGroup($tenant, $setting_group);

            return response()->json(null, 204);
        } catch (TenantRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }
}
