<?php

declare(strict_types=1);

namespace Modules\Vehicle\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

final class VehicleOwnershipController extends Controller
{
    public function index(Request $request, int $vehicleId): JsonResponse
    {
        $tenantId = (int) $request->input('tenant_id', 0);

        $rows = DB::table('vehicle_ownerships')
            ->where('tenant_id', $tenantId)
            ->where('vehicle_id', $vehicleId)
            ->whereNull('deleted_at')
            ->orderByDesc('is_current')
            ->orderByDesc('start_date')
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function current(Request $request, int $vehicleId): JsonResponse
    {
        $tenantId = (int) $request->input('tenant_id', 0);
        $role = (string) $request->input('ownership_role', 'legal_owner');

        $row = DB::table('vehicle_ownerships')
            ->where('tenant_id', $tenantId)
            ->where('vehicle_id', $vehicleId)
            ->where('ownership_role', $role)
            ->where('is_current', true)
            ->whereNull('deleted_at')
            ->orderByDesc('start_date')
            ->first();

        return response()->json(['data' => $row]);
    }

    public function store(Request $request, int $vehicleId): JsonResponse
    {
        $payload = $this->validated($request, $vehicleId);

        return DB::transaction(function () use ($payload): JsonResponse {
            if ((bool) ($payload['is_current'] ?? true)) {
                $this->clearCurrentRole(
                    (int) $payload['tenant_id'],
                    (int) $payload['vehicle_id'],
                    (string) $payload['ownership_role'],
                );
            }

            $id = DB::table('vehicle_ownerships')->insertGetId([
                ...$payload,
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json(['data' => DB::table('vehicle_ownerships')->where('id', $id)->first()], 201);
        });
    }

    public function update(Request $request, int $vehicleId, int $ownershipId): JsonResponse
    {
        $payload = $this->validated($request, $vehicleId, false);

        return DB::transaction(function () use ($payload, $ownershipId): JsonResponse {
            if ((bool) ($payload['is_current'] ?? false)) {
                $this->clearCurrentRole(
                    (int) $payload['tenant_id'],
                    (int) $payload['vehicle_id'],
                    (string) $payload['ownership_role'],
                    $ownershipId,
                );
            }

            DB::table('vehicle_ownerships')
                ->where('id', $ownershipId)
                ->where('tenant_id', (int) $payload['tenant_id'])
                ->update([...$payload, 'updated_at' => now()]);

            return response()->json(['data' => DB::table('vehicle_ownerships')->where('id', $ownershipId)->first()]);
        });
    }

    public function end(Request $request, int $vehicleId, int $ownershipId): JsonResponse
    {
        $tenantId = (int) $request->input('tenant_id', 0);
        $endDate = (string) $request->input('end_date', now()->toDateString());

        DB::table('vehicle_ownerships')
            ->where('id', $ownershipId)
            ->where('tenant_id', $tenantId)
            ->where('vehicle_id', $vehicleId)
            ->update([
                'end_date' => $endDate,
                'is_current' => false,
                'updated_at' => now(),
            ]);

        return response()->json(['data' => DB::table('vehicle_ownerships')->where('id', $ownershipId)->first()]);
    }

    public function setCurrent(Request $request, int $vehicleId, int $ownershipId): JsonResponse
    {
        $tenantId = (int) $request->input('tenant_id', 0);
        $row = DB::table('vehicle_ownerships')
            ->where('id', $ownershipId)
            ->where('tenant_id', $tenantId)
            ->where('vehicle_id', $vehicleId)
            ->whereNull('deleted_at')
            ->first();

        if ($row === null) {
            return response()->json(['message' => 'Ownership record not found.'], 404);
        }

        return DB::transaction(function () use ($row, $ownershipId): JsonResponse {
            $this->clearCurrentRole((int) $row->tenant_id, (int) $row->vehicle_id, (string) $row->ownership_role, $ownershipId);

            DB::table('vehicle_ownerships')->where('id', $ownershipId)->update([
                'is_current' => true,
                'end_date' => null,
                'updated_at' => now(),
            ]);

            return response()->json(['data' => DB::table('vehicle_ownerships')->where('id', $ownershipId)->first()]);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, int $vehicleId, bool $creating = true): array
    {
        $data = $request->validate([
            'tenant_id' => ['required', 'integer', 'min:1', 'exists:tenants,id'],
            'organization_unit_id' => ['nullable', 'integer', 'exists:organization_units,id'],
            'ownership_type' => ['required', 'string', Rule::in(['own', 'customer', 'supplier', 'provider', 'leased', 'financed', 'partner', 'internal', 'external', 'other'])],
            'owner_type' => ['required', 'string', Rule::in(['company', 'customer', 'supplier', 'employee', 'partner', 'external_party', 'party', 'other'])],
            'owner_id' => ['nullable', 'integer', 'min:1'],
            'party_id' => ['nullable', 'integer', 'min:1'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'ownership_role' => ['required', 'string', Rule::in(['legal_owner', 'registered_owner', 'operational_owner', 'provider', 'current_holder'])],
            'start_date' => [$creating ? 'required' : 'sometimes', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_current' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
            'created_by' => ['nullable', 'integer'],
            'updated_by' => ['nullable', 'integer'],
        ]);

        $data['vehicle_id'] = $vehicleId;
        $data['is_current'] = (bool) ($data['is_current'] ?? true);

        $this->validateOwnerReference($data);

        return $data;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function validateOwnerReference(array $payload): void
    {
        $ownerType = (string) $payload['owner_type'];
        if ($ownerType === 'external_party' && empty($payload['owner_name'])) {
            abort(response()->json(['message' => 'owner_name is required for external_party ownership.'], 422));
        }

        $table = match ($ownerType) {
            'customer' => 'customers',
            'supplier' => 'suppliers',
            'employee' => 'employees',
            default => null,
        };

        if ($table === null || ! isset($payload['owner_id'])) {
            return;
        }

        $query = DB::table($table)->where('id', (int) $payload['owner_id'])->where('tenant_id', (int) $payload['tenant_id']);
        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        if (! $query->exists()) {
            abort(response()->json(['message' => 'owner_id must reference a same-tenant owner record.'], 422));
        }
    }

    private function clearCurrentRole(int $tenantId, int $vehicleId, string $role, ?int $exceptId = null): void
    {
        $query = DB::table('vehicle_ownerships')
            ->where('tenant_id', $tenantId)
            ->where('vehicle_id', $vehicleId)
            ->where('ownership_role', $role);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        $query->update([
            'is_current' => false,
            'end_date' => now()->toDateString(),
            'updated_at' => now(),
        ]);
    }
}
