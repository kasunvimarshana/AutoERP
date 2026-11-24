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
use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Hr\Biometric\Jobs\SyncBiometricDeviceJob;
use Modules\Hr\Biometric\Services\BiometricDeviceService;
use Modules\Hr\DTOs\BiometricDeviceData;
use Modules\Hr\Enums\BiometricDeviceStatus;
use Modules\Hr\Enums\BiometricProtocol;
use Modules\Hr\Models\HrBiometricDevice;
use Modules\Hr\Models\HrBiometricDeviceUser;
use Modules\Hr\Models\HrBiometricRawPunch;

final class HrBiometricDeviceController
{
    public function __construct(
        private readonly BiometricDeviceService $deviceService,
    ) {}

    public function index(TenantScopedRequest $request): JsonResponse
    {
        $query = HrBiometricDevice::query()
            ->where('tenant_id', $request->tenantId());

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        if ($request->filled('protocol')) {
            $query->where('protocol', (string) $request->input('protocol'));
        }

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('device_code', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%");
            });
        }

        $devices = $query->orderBy('name')->paginate($request->integer('per_page', 25));

        return response()->json($devices);
    }

    public function store(TenantScopedRequest $request): JsonResponse
    {
        $tenantId = $request->tenantId();

        $validated = $request->validate([
            'device_code' => ['required', 'string', 'max:32', Rule::unique('hr_biometric_devices')->where('tenant_id', $tenantId)],
            'name' => ['required', 'string', 'max:100'],
            'protocol' => ['required', Rule::enum(BiometricProtocol::class)],
            'ip_address' => ['nullable', 'string', 'max:45'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'serial_number' => ['nullable', 'string', 'max:64'],
            'comm_key' => ['nullable', 'string', 'max:64'],
            'auth_token' => ['nullable', 'string', 'max:128'],
            'status' => ['nullable', Rule::enum(BiometricDeviceStatus::class)],
            'is_enabled' => ['boolean'],
            'auto_map_users' => ['boolean'],
            'organization_unit_id' => ['nullable', 'integer'],
            'settings' => ['nullable', 'array'],
        ]);

        $data = new BiometricDeviceData(
            deviceCode: $validated['device_code'],
            name: $validated['name'],
            protocol: BiometricProtocol::from($validated['protocol']),
            ipAddress: $validated['ip_address'] ?? null,
            port: isset($validated['port']) ? (int) $validated['port'] : null,
            serialNumber: $validated['serial_number'] ?? null,
            commKey: $validated['comm_key'] ?? null,
            authToken: $validated['auth_token'] ?? null,
            status: isset($validated['status']) ? BiometricDeviceStatus::from($validated['status']) : BiometricDeviceStatus::Active,
            isEnabled: (bool) ($validated['is_enabled'] ?? true),
            autoMapUsers: (bool) ($validated['auto_map_users'] ?? true),
            organizationUnitId: isset($validated['organization_unit_id']) ? (int) $validated['organization_unit_id'] : $request->organizationUnitId(),
            settings: $validated['settings'] ?? [],
        );

        $device = $this->deviceService->createDevice($tenantId, $data);

        return response()->json(['data' => $device], 201);
    }

    public function show(TenantScopedRequest $request, int $id): JsonResponse
    {
        $device = HrBiometricDevice::query()
            ->where('tenant_id', $request->tenantId())
            ->where('id', $id)
            ->withCount(['deviceUsers', 'rawPunches'])
            ->firstOrFail();

        return response()->json(['data' => $device]);
    }

    public function update(TenantScopedRequest $request, int $id): JsonResponse
    {
        $tenantId = $request->tenantId();

        $device = HrBiometricDevice::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'device_code' => ['required', 'string', 'max:32', Rule::unique('hr_biometric_devices')->where('tenant_id', $tenantId)->ignore($device->id)],
            'name' => ['required', 'string', 'max:100'],
            'protocol' => ['required', Rule::enum(BiometricProtocol::class)],
            'ip_address' => ['nullable', 'string', 'max:45'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'serial_number' => ['nullable', 'string', 'max:64'],
            'comm_key' => ['nullable', 'string', 'max:64'],
            'auth_token' => ['nullable', 'string', 'max:128'],
            'status' => ['nullable', Rule::enum(BiometricDeviceStatus::class)],
            'is_enabled' => ['boolean'],
            'auto_map_users' => ['boolean'],
            'organization_unit_id' => ['nullable', 'integer'],
            'settings' => ['nullable', 'array'],
            'expected_version' => ['required', 'integer'],
        ]);

        $data = new BiometricDeviceData(
            deviceCode: $validated['device_code'],
            name: $validated['name'],
            protocol: BiometricProtocol::from($validated['protocol']),
            ipAddress: $validated['ip_address'] ?? null,
            port: isset($validated['port']) ? (int) $validated['port'] : null,
            serialNumber: $validated['serial_number'] ?? null,
            commKey: $validated['comm_key'] ?? null,
            authToken: $validated['auth_token'] ?? null,
            status: isset($validated['status']) ? BiometricDeviceStatus::from($validated['status']) : $device->status,
            isEnabled: (bool) ($validated['is_enabled'] ?? $device->is_enabled),
            autoMapUsers: (bool) ($validated['auto_map_users'] ?? $device->auto_map_users),
            organizationUnitId: isset($validated['organization_unit_id']) ? (int) $validated['organization_unit_id'] : $device->organization_unit_id,
            settings: $validated['settings'] ?? $device->settings,
        );

        $updated = $this->deviceService->updateDevice($device, $data, (int) $validated['expected_version']);

        return response()->json(['data' => $updated]);
    }

    public function destroy(TenantScopedRequest $request, int $id): JsonResponse
    {
        $device = HrBiometricDevice::query()
            ->where('tenant_id', $request->tenantId())
            ->where('id', $id)
            ->firstOrFail();

        $this->deviceService->deleteDevice($device);

        return response()->json(['message' => 'Device processed successfully.']);
    }

    public function ping(TenantScopedRequest $request, int $id): JsonResponse
    {
        $device = HrBiometricDevice::query()
            ->where('tenant_id', $request->tenantId())
            ->where('id', $id)
            ->firstOrFail();

        $health = $this->deviceService->testConnection($device);

        return response()->json(['data' => $health]);
    }

    public function sync(TenantScopedRequest $request, int $id): JsonResponse
    {
        $device = HrBiometricDevice::query()
            ->where('tenant_id', $request->tenantId())
            ->where('id', $id)
            ->firstOrFail();

        if ($request->boolean('async', false)) {
            SyncBiometricDeviceJob::dispatch((int) $device->id);

            return response()->json(['message' => 'Device synchronization queued successfully.']);
        }

        $result = $this->deviceService->syncDeviceLogs($device);

        return response()->json(['data' => $result]);
    }

    public function users(TenantScopedRequest $request, int $id): JsonResponse
    {
        $device = HrBiometricDevice::query()
            ->where('tenant_id', $request->tenantId())
            ->where('id', $id)
            ->firstOrFail();

        $users = HrBiometricDeviceUser::query()
            ->where('tenant_id', $request->tenantId())
            ->where('device_id', $device->id)
            ->with(['employee:id,employee_number,first_name,last_name'])
            ->orderBy('biometric_user_id')
            ->get();

        return response()->json(['data' => $users]);
    }

    public function storeUser(TenantScopedRequest $request, int $id): JsonResponse
    {
        $device = HrBiometricDevice::query()
            ->where('tenant_id', $request->tenantId())
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'biometric_user_id' => ['required', 'string', 'max:64'],
            'employee_id' => ['required', 'integer'],
            'card_number' => ['nullable', 'string', 'max:64'],
        ]);

        $mapping = $this->deviceService->mapUser(
            $device,
            $validated['biometric_user_id'],
            (int) $validated['employee_id'],
            $validated['card_number'] ?? null,
        );

        return response()->json(['data' => $mapping], 201);
    }

    public function destroyUser(TenantScopedRequest $request, int $id, int $mappingId): JsonResponse
    {
        $mapping = HrBiometricDeviceUser::query()
            ->where('tenant_id', $request->tenantId())
            ->where('device_id', $id)
            ->where('id', $mappingId)
            ->firstOrFail();

        $this->deviceService->unmapUser($mapping);

        return response()->json(['message' => 'Biometric user mapping deleted.']);
    }

    public function rawLogs(TenantScopedRequest $request, int $id): JsonResponse
    {
        $device = HrBiometricDevice::query()
            ->where('tenant_id', $request->tenantId())
            ->where('id', $id)
            ->firstOrFail();

        $query = HrBiometricRawPunch::query()
            ->where('tenant_id', $request->tenantId())
            ->where('device_id', $device->id)
            ->with(['employee:id,employee_number,first_name,last_name', 'attendancePunch:id,punch_type,punch_time']);

        if ($request->filled('sync_status')) {
            $query->where('sync_status', (string) $request->input('sync_status'));
        }

        if ($request->filled('date')) {
            $query->whereDate('punch_time', (string) $request->input('date'));
        }

        $logs = $query->orderByDesc('punch_time')->paginate($request->integer('per_page', 50));

        return response()->json($logs);
    }

    public function reprocess(TenantScopedRequest $request, int $id): JsonResponse
    {
        $device = HrBiometricDevice::query()
            ->where('tenant_id', $request->tenantId())
            ->where('id', $id)
            ->firstOrFail();

        $reprocessed = $this->deviceService->reprocessUnmappedPunches($device);

        return response()->json([
            'message' => "Successfully reprocessed {$reprocessed} unmapped punches.",
            'count' => $reprocessed,
        ]);
    }

    public function lookup(TenantScopedRequest $request): JsonResponse
    {
        $devices = HrBiometricDevice::query()
            ->where('tenant_id', $request->tenantId())
            ->where('is_enabled', true)
            ->select(['id', 'device_code', 'name', 'protocol', 'status', 'ip_address'])
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $devices]);
    }
}
