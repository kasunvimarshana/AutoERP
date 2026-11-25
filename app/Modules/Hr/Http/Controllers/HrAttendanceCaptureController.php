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
use Modules\Hr\Enums\PunchType;
use Modules\Hr\Models\HrAttendanceKiosk;
use Modules\Hr\Services\Capture\AttendanceCaptureService;
use Modules\Hr\Services\Capture\BatchAttendanceImportService;
use Modules\Hr\Services\Capture\DynamicQrSecurityService;

final class HrAttendanceCaptureController
{
    public function __construct(
        private readonly AttendanceCaptureService $captureService,
        private readonly BatchAttendanceImportService $importService,
        private readonly DynamicQrSecurityService $qrSecurityService,
    ) {}

    public function webPunch(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'integer'],
            'punch_type' => ['required', 'string', Rule::enum(PunchType::class)],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

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
            'file' => ['required_without:csv_content', 'nullable', 'file', 'mimes:csv,txt', 'max:5120'],
            'csv_content' => ['required_without:file', 'nullable', 'string', 'max:5242880'],
        ]);

        if ($request->hasFile('file')) {
            $csvContent = (string) file_get_contents($request->file('file')->getRealPath());
        } else {
            $csvContent = (string) ($validated['csv_content'] ?? '');
        }

        $preview = $this->importService->preview($request->tenantId(), $csvContent);

        return response()->json(['data' => $preview]);
    }

    public function commitImport(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'file_name' => ['required', 'string', 'max:255'],
            'csv_content' => ['required', 'string'],
        ]);

        $result = $this->importService->commit(
            tenantId: $request->tenantId(),
            userId: (int) $request->userId(),
            fileName: $validated['file_name'],
            csvContent: $validated['csv_content'],
        );

        return response()->json(['data' => $result], 201);
    }
}
