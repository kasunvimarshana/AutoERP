<?php

namespace Modules\Configuration\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Configuration\Application\Contracts\GetSettingGroupServiceInterface;
use Modules\Configuration\Application\Contracts\GetSettingServiceInterface;
use Modules\Configuration\Application\Contracts\SetSettingServiceInterface;
use Modules\Configuration\Infrastructure\Http\Resources\SystemSettingResource;

class SystemSettingController extends Controller
{
    public function __construct(
        private readonly GetSettingServiceInterface $getService,
        private readonly GetSettingGroupServiceInterface $getGroupService,
        private readonly SetSettingServiceInterface $setService,
    ) {}

    public function getGroup(int $tenantId, string $group): JsonResponse
    {
        $settings = $this->getGroupService->execute($tenantId, $group);
        return response()->json(array_map(fn ($s) => new SystemSettingResource($s), $settings));
    }

    public function get(int $tenantId, string $group, string $key): JsonResponse
    {
        $setting = $this->getService->execute($tenantId, $group, $key);
        if (!$setting) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json(new SystemSettingResource($setting));
    }

    public function set(Request $request, int $tenantId, string $group, string $key): JsonResponse
    {
        $request->validate([
            'value' => ['nullable', 'string'],
            'type'  => ['sometimes', 'string', 'in:string,integer,float,boolean,json,text'],
        ]);
        $setting = $this->setService->execute(
            $tenantId,
            $group,
            $key,
            $request->input('value'),
            $request->input('type', 'string')
        );
        return response()->json(new SystemSettingResource($setting));
    }
}
