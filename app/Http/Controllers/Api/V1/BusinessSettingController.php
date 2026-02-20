<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\BusinessSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusinessSettingController extends Controller
{
    public function __construct(
        private readonly BusinessSettingService $settingService
    ) {}

    /**
     * GET /api/v1/settings
     * List all settings for the authenticated tenant, optionally filtered by group.
     */
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('settings.view'), 403);

        $tenantId = $request->user()->tenant_id;
        $group = $request->query('group');

        return response()->json([
            'data' => $this->settingService->all($tenantId, $group),
        ]);
    }

    /**
     * PUT /api/v1/settings
     * Bulk-upsert settings (key-value pairs) for the authenticated tenant.
     */
    public function upsert(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('settings.edit'), 403);

        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*.key' => ['required', 'string', 'max:255'],
            'settings.*.value' => ['nullable', 'string'],
            'settings.*.group' => ['nullable', 'string', 'max:100'],
            'settings.*.is_public' => ['nullable', 'boolean'],
            'group' => ['nullable', 'string', 'max:100'],
        ]);

        $tenantId = $request->user()->tenant_id;
        $defaultGroup = $validated['group'] ?? 'general';

        foreach ($validated['settings'] as $item) {
            $this->settingService->set(
                tenantId: $tenantId,
                key: $item['key'],
                value: $item['value'] ?? null,
                group: $item['group'] ?? $defaultGroup,
                isPublic: (bool) ($item['is_public'] ?? false),
            );
        }

        return response()->json([
            'data' => $this->settingService->all($tenantId, $request->query('group')),
            'message' => 'Settings saved.',
        ]);
    }

    /**
     * DELETE /api/v1/settings/{key}
     * Delete a single setting for the authenticated tenant.
     */
    public function destroy(Request $request, string $key): JsonResponse
    {
        abort_unless($request->user()?->can('settings.edit'), 403);

        $tenantId = $request->user()->tenant_id;
        $this->settingService->delete($tenantId, $key);

        return response()->json(null, 204);
    }
}
