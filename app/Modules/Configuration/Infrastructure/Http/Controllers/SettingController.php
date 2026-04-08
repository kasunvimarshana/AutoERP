<?php

declare(strict_types=1);

namespace Modules\Configuration\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Configuration\Application\Contracts\SettingServiceInterface;
use Modules\Configuration\Infrastructure\Http\Resources\SettingResource;

class SettingController extends Controller
{
    public function __construct(
        private readonly SettingServiceInterface $settingService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $group = $request->query('group');

        $settings = $group
            ? $this->settingService->getSettingsByGroup($tenantId, (string) $group)
            : $this->settingService->getAllSettings($tenantId);

        return response()->json(SettingResource::collection(collect($settings)));
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $setting = $this->settingService->getSetting($request->user()->tenant_id, $id);

        return response()->json(new SettingResource($setting));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'key'         => 'required|string|max:255',
            'value'       => 'nullable',
            'group'       => 'sometimes|string|max:100',
            'type'        => 'sometimes|in:string,integer,float,boolean,json,array',
            'is_public'   => 'sometimes|boolean',
            'description' => 'nullable|string',
        ]);

        $setting = $this->settingService->createSetting($request->user()->tenant_id, $data);

        return response()->json(new SettingResource($setting), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'key'         => 'sometimes|string|max:255',
            'value'       => 'nullable',
            'group'       => 'sometimes|string|max:100',
            'type'        => 'sometimes|in:string,integer,float,boolean,json,array',
            'is_public'   => 'sometimes|boolean',
            'description' => 'nullable|string',
        ]);

        $setting = $this->settingService->updateSetting($request->user()->tenant_id, $id, $data);

        return response()->json(new SettingResource($setting));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->settingService->deleteSetting($request->user()->tenant_id, $id);

        return response()->json(null, 204);
    }
}
