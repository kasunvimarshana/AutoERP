<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Hr\Models\HrAttendanceGeofence;
use Modules\Hr\Models\HrAttendanceKiosk;

final class HrAttendanceGeofenceController
{
    public function indexGeofences(TenantScopedRequest $request): JsonResponse
    {
        $geofences = HrAttendanceGeofence::query()
            ->where('tenant_id', $request->tenantId())
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $geofences]);
    }

    public function storeGeofence(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:32'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'radius_meters' => ['required', 'integer', 'min:10', 'max:50000'],
            'ip_whitelist' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $geofence = HrAttendanceGeofence::create([
            'tenant_id' => $request->tenantId(),
            'organization_unit_id' => $request->organizationUnitId(),
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'radius_meters' => $validated['radius_meters'],
            'ip_whitelist' => $validated['ip_whitelist'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json(['data' => $geofence], 201);
    }

    public function updateGeofence(TenantScopedRequest $request, int $geofence): JsonResponse
    {
        $model = HrAttendanceGeofence::query()
            ->where('tenant_id', $request->tenantId())
            ->where('id', $geofence)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:32'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'radius_meters' => ['required', 'integer', 'min:10', 'max:50000'],
            'ip_whitelist' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $model->update([
            'row_version' => $model->row_version + 1,
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'radius_meters' => $validated['radius_meters'],
            'ip_whitelist' => $validated['ip_whitelist'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json(['data' => $model->refresh()]);
    }

    public function destroyGeofence(TenantScopedRequest $request, int $geofence): JsonResponse
    {
        $model = HrAttendanceGeofence::query()
            ->where('tenant_id', $request->tenantId())
            ->where('id', $geofence)
            ->firstOrFail();

        $model->delete();

        return response()->json(null, 204);
    }

    public function indexKiosks(TenantScopedRequest $request): JsonResponse
    {
        $kiosks = HrAttendanceKiosk::query()
            ->where('tenant_id', $request->tenantId())
            ->with(['geofence'])
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $kiosks]);
    }

    public function storeKiosk(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'kiosk_code' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:120'],
            'secret_key' => ['nullable', 'string', 'max:64'],
            'rotation_seconds' => ['nullable', 'integer', 'min:10', 'max:300'],
            'geofence_id' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $kiosk = HrAttendanceKiosk::create([
            'tenant_id' => $request->tenantId(),
            'organization_unit_id' => $request->organizationUnitId(),
            'kiosk_code' => strtoupper($validated['kiosk_code']),
            'name' => $validated['name'],
            'secret_key' => $validated['secret_key'] ?? bin2hex(random_bytes(16)),
            'rotation_seconds' => $validated['rotation_seconds'] ?? 20,
            'geofence_id' => $validated['geofence_id'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json(['data' => $kiosk], 201);
    }

    public function updateKiosk(TenantScopedRequest $request, int $kiosk): JsonResponse
    {
        $model = HrAttendanceKiosk::query()
            ->where('tenant_id', $request->tenantId())
            ->where('id', $kiosk)
            ->firstOrFail();

        $validated = $request->validate([
            'kiosk_code' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:120'],
            'secret_key' => ['nullable', 'string', 'max:64'],
            'rotation_seconds' => ['nullable', 'integer', 'min:10', 'max:300'],
            'geofence_id' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $model->update([
            'row_version' => $model->row_version + 1,
            'kiosk_code' => strtoupper($validated['kiosk_code']),
            'name' => $validated['name'],
            'secret_key' => $validated['secret_key'] ?? $model->secret_key,
            'rotation_seconds' => $validated['rotation_seconds'] ?? $model->rotation_seconds,
            'geofence_id' => $validated['geofence_id'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json(['data' => $model->refresh()]);
    }

    public function destroyKiosk(TenantScopedRequest $request, int $kiosk): JsonResponse
    {
        $model = HrAttendanceKiosk::query()
            ->where('tenant_id', $request->tenantId())
            ->where('id', $kiosk)
            ->firstOrFail();

        $model->delete();

        return response()->json(null, 204);
    }
}
