<?php

declare(strict_types=1);

namespace Modules\Tenant\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Tenant\Application\UseCases\Settings\TenantSettingService;
use Modules\Tenant\Presentation\Http\Requests\ListTenantSettingRequest;
use Modules\Tenant\Presentation\Http\Requests\UpsertTenantSettingRequest;
use Modules\Tenant\Presentation\Http\Resources\TenantSettingResource;

final class TenantSettingController extends Controller
{
    public function __construct(private readonly TenantSettingService $settings) {}

    public function index(ListTenantSettingRequest $request): JsonResponse
    {
        $result = $this->settings->listByTenant((int) $request->validated('tenant_id'));
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return response()->json(['data' => TenantSettingResource::collection($result->valueOrFail())->resolve()]);
    }

    public function show(int|string $tenantSetting): JsonResponse|TenantSettingResource
    {
        $result = $this->settings->get($tenantSetting);
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return new TenantSettingResource($result->valueOrFail());
    }

    public function store(UpsertTenantSettingRequest $request): JsonResponse|TenantSettingResource
    {
        $result = $this->settings->create($request->validated());
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 422);
        }

        return (new TenantSettingResource($result->valueOrFail()))->response()->setStatusCode(201);
    }

    public function update(UpsertTenantSettingRequest $request, int|string $tenantSetting): JsonResponse|TenantSettingResource
    {
        $result = $this->settings->update($tenantSetting, $request->validated());
        if ($result->isFailure()) {
            $status = $result->errorOrFail()->code === 'TENANT_NOT_FOUND' ? 404 : 422;

            return response()->json(['message' => $result->errorOrFail()->message], $status);
        }

        return new TenantSettingResource($result->valueOrFail());
    }

    public function destroy(int|string $tenantSetting): JsonResponse
    {
        $result = $this->settings->delete($tenantSetting);
        if ($result->isFailure()) {
            return response()->json(['message' => $result->errorOrFail()->message], 404);
        }

        return response()->json(null, 204);
    }
}
