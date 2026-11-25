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

use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Hr\Enums\CredentialStatus;
use Modules\Hr\Enums\CredentialType;
use Modules\Hr\Models\HrEmployee;
use Modules\Hr\Models\HrEmployeeAttendanceCredential;

final class HrAttendanceCredentialController
{
    public function index(TenantScopedRequest $request, int $employee): JsonResponse
    {
        $credentials = HrEmployeeAttendanceCredential::query()
            ->where('tenant_id', $request->tenantId())
            ->where('employee_id', $employee)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $credentials]);
    }

    public function store(TenantScopedRequest $request, int $employee): JsonResponse
    {
        $emp = HrEmployee::query()
            ->where('tenant_id', $request->tenantId())
            ->where('id', $employee)
            ->firstOrFail();

        $validated = $request->validate([
            'credential_type' => ['required', 'string'],
            'credential_value' => ['required', 'string', 'max:128'],
            'label' => ['nullable', 'string', 'max:120'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $credential = HrEmployeeAttendanceCredential::create([
            'tenant_id' => $request->tenantId(),
            'organization_unit_id' => $emp->organization_unit_id,
            'employee_id' => $employee,
            'credential_type' => CredentialType::from($validated['credential_type']),
            'credential_value' => trim($validated['credential_value']),
            'label' => $validated['label'] ?? null,
            'status' => CredentialStatus::Active,
            'issued_at' => CarbonImmutable::now(),
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        return response()->json(['data' => $credential], 201);
    }

    public function updateStatus(TenantScopedRequest $request, int $employee, int $credential): JsonResponse
    {
        $cred = HrEmployeeAttendanceCredential::query()
            ->where('tenant_id', $request->tenantId())
            ->where('employee_id', $employee)
            ->where('id', $credential)
            ->firstOrFail();

        $validated = $request->validate([
            'status' => ['required', 'string'],
            'revocation_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $newStatus = CredentialStatus::from($validated['status']);
        $updates = [
            'status' => $newStatus,
            'row_version' => $cred->row_version + 1,
        ];

        if (in_array($newStatus, [CredentialStatus::Revoked, CredentialStatus::Lost], true)) {
            $updates['revoked_at'] = CarbonImmutable::now();
            $updates['revocation_reason'] = $validated['revocation_reason'] ?? null;
        }

        $cred->update($updates);

        return response()->json(['data' => $cred->refresh()]);
    }

    public function destroy(TenantScopedRequest $request, int $employee, int $credential): JsonResponse
    {
        $cred = HrEmployeeAttendanceCredential::query()
            ->where('tenant_id', $request->tenantId())
            ->where('employee_id', $employee)
            ->where('id', $credential)
            ->firstOrFail();

        $cred->delete();

        return response()->json(null, 204);
    }
}
