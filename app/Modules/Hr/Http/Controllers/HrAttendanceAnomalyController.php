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
use Modules\Hr\Models\HrAttendanceRecord;
use Modules\Hr\Services\AttendanceAnomalyService;

final class HrAttendanceAnomalyController
{
    public function __construct(
        private readonly AttendanceAnomalyService $anomalyService,
    ) {}

    public function index(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'employee_id' => ['nullable', 'integer'],
            'anomaly_type' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $query = HrAttendanceRecord::query()
            ->where('tenant_id', $request->tenantId())
            ->where(function ($q) {
                $q->where('requires_regularization', true)
                    ->orWhereNotNull('anomaly_type');
            })
            ->with(['employee.department', 'shift', 'punches'])
            ->orderBy('attendance_date', 'desc');

        if (! empty($validated['date'])) {
            $query->whereDate('attendance_date', $validated['date']);
        }
        if (! empty($validated['employee_id'])) {
            $query->where('employee_id', $validated['employee_id']);
        }
        if (! empty($validated['anomaly_type'])) {
            $query->where('anomaly_type', $validated['anomaly_type']);
        }

        $records = $query->paginate($validated['per_page'] ?? 25);

        return response()->json($records);
    }

    public function scan(TenantScopedRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'employee_id' => ['nullable', 'integer'],
        ]);

        $result = $this->anomalyService->scanAnomalies(
            tenantId: $request->tenantId(),
            date: $validated['date'] ?? null,
            employeeId: isset($validated['employee_id']) ? (int) $validated['employee_id'] : null,
        );

        return response()->json(['data' => $result]);
    }

    public function quickResolve(TenantScopedRequest $request, int $record): JsonResponse
    {
        $validated = $request->validate([
            'in_time' => ['nullable', 'date'],
            'out_time' => ['nullable', 'date'],
            'resolution_reason' => ['required', 'string', 'max:255'],
        ]);

        $attendanceRecord = HrAttendanceRecord::query()
            ->where('tenant_id', $request->tenantId())
            ->where('id', $record)
            ->firstOrFail();

        $resolved = $this->anomalyService->quickResolveAnomaly(
            record: $attendanceRecord,
            resolvedByUserId: (int) $request->userId(),
            inTime: $validated['in_time'] ?? null,
            outTime: $validated['out_time'] ?? null,
            resolutionReason: $validated['resolution_reason'],
        );

        return response()->json(['data' => $resolved]);
    }
}
