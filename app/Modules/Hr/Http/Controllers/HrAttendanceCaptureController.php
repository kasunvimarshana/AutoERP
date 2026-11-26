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
use Modules\Core\Contracts\PermissionCheckerInterface;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Hr\Enums\CaptureMethod;
use Modules\Hr\Enums\PunchType;
use Modules\Hr\Models\HrAttendanceKiosk;
use Modules\Hr\Models\HrEmployee;
use Modules\Hr\Services\Capture\AttendanceCaptureService;
use Modules\Hr\Services\Capture\BatchAttendanceImportService;
use Modules\Hr\Services\Capture\DynamicQrSecurityService;
use Modules\Hr\Services\HrAuthorizationService;

final class HrAttendanceCaptureController
{
    public function __construct(
        private readonly AttendanceCaptureService $captureService,
        private readonly BatchAttendanceImportService $importService,
        private readonly DynamicQrSecurityService $qrSecurityService,
        private readonly PermissionCheckerInterface $permissions,
    ) {}

    public function capture(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'capture_method' => ['required', 'string', Rule::enum(CaptureMethod::class)],
            'payload' => ['required', 'array'],
        ]);

        $method = CaptureMethod::from($validated['capture_method']);
        $payload = $validated['payload'];
        if (isset($payload['employee_id']) && is_numeric($payload['employee_id'])) {
            if ($forbidden = $this->authorizeEmployeeCapture($request, (int) $payload['employee_id'])) {
                return $forbidden;
            }
        }
        $payload['ip_address'] ??= $request->ip();
        $payload['organization_unit_id'] ??= $request->organizationUnitId();

        $punch = $this->captureService->capture($request->tenantId(), $method, $payload);

        return response()->json(['data' => $punch], 201);
    }

    public function webPunch(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'integer'],
            'punch_type' => ['required', 'string', Rule::enum(PunchType::class)],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        if ($forbidden = $this->authorizeEmployeeCapture($request, (int) $validated['employee_id'])) {
            return $forbidden;
        }

        $punch = $this->captureService->recordWebPunch(
            tenantId: $request->tenantId(),
            employeeId: (int) $validated['employee_id'],
            punchType: PunchType::from($validated['punch_type']),
            ipAddress: $request->ip(),
            notes: $validated['notes'] ?? null,
        );

        return response()->json(['data' => $punch], 201);
    }

    public function mobilePunch(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'integer'],
            'punch_type' => ['required', 'string', Rule::enum(PunchType::class)],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'accuracy_meters' => ['nullable', 'integer', 'min:0'],
            'device_uuid' => ['nullable', 'string', 'max:128'],
            'photo_path' => ['nullable', 'string', 'max:255'],
            'geofence_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        if ($forbidden = $this->authorizeEmployeeCapture($request, (int) $validated['employee_id'])) {
            return $forbidden;
        }

        $punch = $this->captureService->recordMobilePunch(
            tenantId: $request->tenantId(),
            employeeId: (int) $validated['employee_id'],
            punchType: PunchType::from($validated['punch_type']),
            latitude: (float) $validated['latitude'],
            longitude: (float) $validated['longitude'],
            accuracyMeters: isset($validated['accuracy_meters']) ? (int) $validated['accuracy_meters'] : null,
            deviceUuid: $validated['device_uuid'] ?? null,
            photoPath: $validated['photo_path'] ?? null,
            notes: $validated['notes'] ?? null,
            ipAddress: $request->ip(),
            geofenceId: isset($validated['geofence_id']) ? (int) $validated['geofence_id'] : null,
        );

        return response()->json(['data' => $punch], 201);
    }

    public function rfidPunch(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'card_uid' => ['required', 'string', 'max:128'],
            'punch_type' => ['required', 'string', Rule::enum(PunchType::class)],
            'device_id' => ['nullable', 'string', 'max:64'],
        ]);

        $punch = $this->captureService->recordRfidPunch(
            tenantId: $request->tenantId(),
            cardUid: $validated['card_uid'],
            punchType: PunchType::from($validated['punch_type']),
            deviceId: $validated['device_id'] ?? null,
            ipAddress: $request->ip(),
        );

        return response()->json(['data' => $punch], 201);
    }

    public function nfcPunch(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'tag_uid' => ['required', 'string', 'max:128'],
            'punch_type' => ['required', 'string', Rule::enum(PunchType::class)],
            'device_id' => ['nullable', 'string', 'max:64'],
            'device_uuid' => ['nullable', 'string', 'max:128'],
        ]);

        $punch = $this->captureService->recordNfcPunch(
            tenantId: $request->tenantId(),
            tagUid: $validated['tag_uid'],
            punchType: PunchType::from($validated['punch_type']),
            deviceId: $validated['device_id'] ?? null,
            deviceUuid: $validated['device_uuid'] ?? null,
            ipAddress: $request->ip(),
        );

        return response()->json(['data' => $punch], 201);
    }

    public function barcodePunch(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'barcode' => ['required', 'string', 'max:128'],
            'punch_type' => ['required', 'string', Rule::enum(PunchType::class)],
            'device_id' => ['nullable', 'string', 'max:64'],
        ]);

        $punch = $this->captureService->recordBarcodePunch(
            tenantId: $request->tenantId(),
            barcode: $validated['barcode'],
            punchType: PunchType::from($validated['punch_type']),
            deviceId: $validated['device_id'] ?? null,
        );

        return response()->json(['data' => $punch], 201);
    }

    public function kioskQrToken(TenantScopedRequest $request, int $kiosk): JsonResponse
    {
        $kioskModel = HrAttendanceKiosk::query()
            ->where('tenant_id', $request->tenantId())
            ->where('id', $kiosk)
            ->where('is_active', true)
            ->firstOrFail();

        $tokenData = $this->qrSecurityService->generateToken($kioskModel);

        return response()->json(['data' => $tokenData]);
    }

    public function scanKioskQr(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'integer'],
            'qr_token' => ['required', 'string'],
            'punch_type' => ['required', 'string', Rule::enum(PunchType::class)],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'device_uuid' => ['nullable', 'string', 'max:128'],
        ]);

        if ($forbidden = $this->authorizeEmployeeCapture($request, (int) $validated['employee_id'])) {
            return $forbidden;
        }

        $punch = $this->captureService->recordKioskQrPunch(
            tenantId: $request->tenantId(),
            employeeId: (int) $validated['employee_id'],
            qrToken: $validated['qr_token'],
            punchType: PunchType::from($validated['punch_type']),
            latitude: isset($validated['latitude']) ? (float) $validated['latitude'] : null,
            longitude: isset($validated['longitude']) ? (float) $validated['longitude'] : null,
            deviceUuid: $validated['device_uuid'] ?? null,
        );

        return response()->json(['data' => $punch], 201);
    }

    public function previewImport(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required_without:csv_content', 'nullable', 'file', 'mimes:csv,txt,xlsx', 'max:5120'],
            'csv_content' => ['required_without:file', 'nullable', 'string', 'max:5242880'],
        ]);

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->getRealPath() ?: null;
            $csvContent = $filePath !== null ? (string) file_get_contents($filePath) : '';
        } else {
            $csvContent = (string) ($validated['csv_content'] ?? '');
        }

        $preview = $this->importService->preview($request->tenantId(), $csvContent, $filePath);

        return response()->json(['data' => $preview]);
    }

    public function commitImport(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'file_name' => ['required_without:file', 'nullable', 'string', 'max:255'],
            'file' => ['required_without:csv_content', 'nullable', 'file', 'mimes:csv,txt,xlsx', 'max:5120'],
            'csv_content' => ['required_without:file', 'nullable', 'string', 'max:5242880'],
        ]);

        $filePath = null;
        if ($request->hasFile('file')) {
            $uploaded = $request->file('file');
            $filePath = $uploaded->getRealPath() ?: null;
            $fileName = (string) ($validated['file_name'] ?? $uploaded->getClientOriginalName());
            $csvContent = $filePath !== null ? (string) file_get_contents($filePath) : '';
        } else {
            $fileName = (string) $validated['file_name'];
            $csvContent = (string) ($validated['csv_content'] ?? '');
        }

        $result = $this->importService->commit(
            tenantId: $request->tenantId(),
            userId: (int) $request->userId(),
            fileName: $fileName,
            csvContent: $csvContent,
            filePath: $filePath,
        );

        return response()->json(['data' => $result], 201);
    }

    private function authorizeEmployeeCapture(TenantScopedRequest $request, int $employeeId): ?JsonResponse
    {
        $tenantId = $request->tenantId();
        $currentUserId = (int) $request->currentUserId();

        if ($this->permissions->allows($currentUserId, $tenantId, HrAuthorizationService::MANAGE_ATTENDANCE)) {
            return null;
        }

        $ownsEmployee = HrEmployee::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $employeeId)
            ->where('user_id', $currentUserId)
            ->exists();

        if (! $ownsEmployee) {
            return response()->json([
                'message' => 'You can only record attendance punches for your own employee profile.',
            ], 403);
        }

        return null;
    }
}
