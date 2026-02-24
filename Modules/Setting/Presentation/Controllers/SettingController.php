<?php
namespace Modules\Setting\Presentation\Controllers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Setting\Application\UseCases\GetSettingGroupUseCase;
use Modules\Setting\Application\UseCases\GetSettingUseCase;
use Modules\Setting\Application\UseCases\UpdateSettingUseCase;
use Modules\Shared\Application\ResponseFormatter;
class SettingController extends Controller
{
    public function __construct(
        private GetSettingUseCase $getUseCase,
        private UpdateSettingUseCase $updateUseCase,
        private GetSettingGroupUseCase $getGroupUseCase,
    ) {}
    public function group(Request $request, string $group): JsonResponse
    {
        $tenantId = app('current.tenant.id') ?? $request->header('X-Tenant-ID', '');
        $settings = $this->getGroupUseCase->execute(['group' => $group, 'tenant_id' => $tenantId]);
        return ResponseFormatter::success($settings);
    }
    public function show(Request $request, string $key): JsonResponse
    {
        $tenantId = app('current.tenant.id') ?? $request->header('X-Tenant-ID', '');
        $value = $this->getUseCase->execute(['key' => $key, 'tenant_id' => $tenantId]);
        return ResponseFormatter::success(['key' => $key, 'value' => $value]);
    }
    public function update(Request $request, string $key): JsonResponse
    {
        $request->validate([
            'value' => ['required'],
            'group' => ['required', 'string'],
            'type' => ['nullable', 'string'],
        ]);
        $tenantId = app('current.tenant.id') ?? $request->header('X-Tenant-ID', '');
        $this->updateUseCase->execute(array_merge($request->validated(), ['key' => $key, 'tenant_id' => $tenantId]));
        return ResponseFormatter::success(null, 'Setting updated.');
    }
}
