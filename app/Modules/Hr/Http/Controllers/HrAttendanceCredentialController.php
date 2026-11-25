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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Hr\Enums\CredentialStatus;
use Modules\Hr\Enums\CredentialType;
use Modules\Hr\Models\HrEmployee;
use Modules\Hr\Models\HrEmployeeAttendanceCredential;

final class HrAttendanceCredentialController
{
    public function index(TenantScopedRequest $request, int $employee): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->input('per_page', 25)));

        $credentials = HrEmployeeAttendanceCredential::query()
            ->where('tenant_id', $request->tenantId())
            ->where('employee_id', $employee)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json($credentials);
    }

    public function store(TenantScopedRequest $request, int $employee): JsonResponse
    {
        $emp = HrEmployee::query()
            ->where('tenant_id', $request->tenantId())
            ->where('id', $employee)
            ->firstOrFail();

        $validated = $request->validate([
            'credential_type' => ['required', 'string', Rule::enum(CredentialType::class)],
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
        $validated = $request->validate([
            'expected_version' => ['sometimes', 'integer', 'min:1'],
            'status' => ['required', 'string', Rule::enum(CredentialStatus::class)],
            'revocation_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $expectedVersion = isset($validated['expected_version']) ? (int) $validated['expected_version'] : null;
        $newStatus = CredentialStatus::from($validated['status']);

        $updated = DB::transaction(function () use ($request, $employee, $credential, $expectedVersion, $newStatus, $validated): ?HrEmployeeAttendanceCredential {
            $cred = HrEmployeeAttendanceCredential::query()
                ->where('tenant_id', $request->tenantId())
                ->where('employee_id', $employee)
                ->where('id', $credential)
                ->lockForUpdate()
                ->firstOrFail();

            if ($expectedVersion !== null && (int) $cred->row_version !== $expectedVersion) {
                return null;
            }

            $updates = [
                'status' => $newStatus,
                'row_version' => ((int) $cred->row_version) + 1,
            ];

            if (in_array($newStatus, [CredentialStatus::Revoked, CredentialStatus::Lost], true)) {
                $updates['revoked_at'] = CarbonImmutable::now();
                $updates['revocation_reason'] = $validated['revocation_reason'] ?? null;
            }

            $cred->update($updates);

            return $cred->refresh();
        });

        if ($updated === null) {
            return response()->json(['message' => 'Credential was changed by someone else. Reload before saving.'], 409);
        }

        return response()->json(['data' => $updated]);
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
