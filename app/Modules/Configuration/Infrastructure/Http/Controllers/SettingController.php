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
    public function __construct(private readonly SettingServiceInterface $service) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = (int) $request->get('tenant_id', 0);
        return response()->json($this->service->getAll($tenantId));
    }

    public function show(Request $request, string $key): JsonResponse
    {
        $tenantId = (int) $request->get('tenant_id', 0);
        $value = $this->service->get($tenantId, $key);
        return response()->json(['key' => $key, 'value' => $value]);
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = (int) $request->get('tenant_id', 0);
        $setting = $this->service->set(
            $tenantId,
            $request->get('key'),
            $request->get('value'),
            $request->get('type', 'string'),
            $request->get('description')
        );
        return response()->json(new SettingResource($setting), 201);
    }

    public function destroy(Request $request, string $key): JsonResponse
    {
        $tenantId = (int) $request->get('tenant_id', 0);
        $this->service->delete($tenantId, $key);
        return response()->json(null, 204);
    }
}
