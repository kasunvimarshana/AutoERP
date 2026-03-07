<?php
namespace App\Modules\Tenant\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\TenantConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function __construct(private TenantConfigService $tenantConfigService) {}

    public function index(): JsonResponse
    {
        $tenants = Tenant::with('configs')->paginate(15);
        return response()->json(['success' => true, 'data' => $tenants]);
    }

    public function show(int $id): JsonResponse
    {
        $tenant = Tenant::with('configs')->findOrFail($id);
        return response()->json(['success' => true, 'data' => $tenant]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'required|string|max:255|unique:tenants,domain',
            'settings' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $tenant = Tenant::create($validated);

        return response()->json(['success' => true, 'data' => $tenant, 'message' => 'Tenant created'], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'domain' => "sometimes|string|max:255|unique:tenants,domain,{$id}",
            'settings' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ]);

        $tenant = Tenant::findOrFail($id);
        $tenant->update($validated);

        return response()->json(['success' => true, 'data' => $tenant, 'message' => 'Tenant updated']);
    }

    public function destroy(int $id): JsonResponse
    {
        Tenant::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Tenant deleted']);
    }

    public function getConfig(int $id): JsonResponse
    {
        $tenant = Tenant::with('configs')->findOrFail($id);
        return response()->json(['success' => true, 'data' => $tenant->configs]);
    }

    public function setConfig(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'key' => 'required|string|max:255',
            'value' => 'required',
            'group' => 'nullable|string|max:100',
            'type' => 'nullable|string|in:string,boolean,integer,float,array,json',
        ]);

        $this->tenantConfigService->setConfig(
            $id,
            $validated['key'],
            $validated['value'],
            $validated['group'] ?? 'general',
            $validated['type'] ?? 'string'
        );

        $this->tenantConfigService->invalidateCache($id);

        return response()->json(['success' => true, 'message' => 'Configuration saved']);
    }
}
