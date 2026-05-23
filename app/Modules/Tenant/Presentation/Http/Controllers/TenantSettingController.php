<?php

declare(strict_types=1);

namespace Modules\Tenant\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Tenant\Application\DTOs\TenantSettingData;
use Modules\Tenant\Application\Services\TenantService;
use Modules\Tenant\Domain\Exceptions\TenantRecordNotFoundException;
use Modules\Tenant\Presentation\Http\Controllers\Concerns\HandlesTenantHttp;
use Modules\Tenant\Presentation\Http\Requests\StoreTenantSettingRequest;
use Modules\Tenant\Presentation\Http\Requests\UpdateTenantSettingRequest;
use Modules\Tenant\Presentation\Http\Resources\TenantSettingResource;

class TenantSettingController extends Controller
{
    use HandlesTenantHttp;

    public function __construct(private readonly TenantService $tenants) {}

    public function index(Request $request, int|string $tenant): mixed
    {
        try {
            return TenantSettingResource::collection($this->tenants->listSettings($tenant, $this->perPage($request)));
        } catch (TenantRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function store(StoreTenantSettingRequest $request, int|string $tenant): JsonResponse
    {
        try {
            $setting = $this->tenants->createSetting(TenantSettingData::fromArray($tenant, $request->validated()));

            return (new TenantSettingResource($setting))->response()->setStatusCode(201);
        } catch (TenantRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function show(int|string $tenant, int|string $setting): TenantSettingResource|JsonResponse
    {
        try {
            return new TenantSettingResource($this->tenants->findSetting($tenant, $setting));
        } catch (TenantRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function update(UpdateTenantSettingRequest $request, int|string $tenant, int|string $setting): TenantSettingResource|JsonResponse
    {
        try {
            return new TenantSettingResource($this->tenants->updateSetting($tenant, $setting, TenantSettingData::fromArray($tenant, $request->validated())));
        } catch (TenantRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }

    public function destroy(int|string $tenant, int|string $setting): JsonResponse
    {
        try {
            $this->tenants->deleteSetting($tenant, $setting);

            return response()->json(null, 204);
        } catch (TenantRecordNotFoundException $exception) {
            return $this->notFound($exception);
        }
    }
}
