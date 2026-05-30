<?php

declare(strict_types=1);

namespace Modules\Vehicle\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Modules\Core\Application\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Result;
use Modules\Vehicle\Application\Contracts\Services\VehicleOwnershipServiceInterface;
use Modules\Vehicle\Domain\Constants\VehicleErrorCode;

final class VehicleOwnershipController extends Controller
{
    public function __construct(
        private readonly VehicleOwnershipServiceInterface $ownerships,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
    ) {
    }

    public function index(Request $request, int $vehicleId): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);

        return $this->toResponse($this->ownerships->list($tenantId, $vehicleId));
    }

    public function current(Request $request, int $vehicleId): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);
        $role = (string) $request->input('ownership_role', 'legal_owner');

        return $this->toResponse($this->ownerships->current($tenantId, $vehicleId, $role));
    }

    public function store(Request $request, int $vehicleId): JsonResponse
    {
        $payload = $this->validated($request, $vehicleId);

        return $this->toResponse($this->ownerships->create($vehicleId, $payload), 201);
    }

    public function update(Request $request, int $vehicleId, int $ownershipId): JsonResponse
    {
        $payload = $this->validated($request, $vehicleId, false);

        return $this->toResponse($this->ownerships->update($vehicleId, $ownershipId, $payload));
    }

    public function end(Request $request, int $vehicleId, int $ownershipId): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => ['nullable', 'integer', 'min:1', 'exists:tenants,id'],
            'end_date' => ['required', 'date'],
        ]);

        return $this->toResponse($this->ownerships->end(
            $this->resolveTenantId($request),
            $vehicleId,
            $ownershipId,
            (string) $data['end_date'],
        ));
    }

    public function setCurrent(Request $request, int $vehicleId, int $ownershipId): JsonResponse
    {
        $request->validate([
            'tenant_id' => ['nullable', 'integer', 'min:1', 'exists:tenants,id'],
        ]);

        return $this->toResponse($this->ownerships->setCurrent($this->resolveTenantId($request), $vehicleId, $ownershipId));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, int $vehicleId, bool $creating = true): array
    {
        $data = $request->validate([
            'tenant_id' => ['nullable', 'integer', 'min:1', 'exists:tenants,id'],
            'organization_unit_id' => ['nullable', 'integer', 'exists:organization_units,id'],
            'ownership_type' => ['required', 'string', Rule::in(['own', 'customer', 'supplier', 'provider', 'leased', 'financed', 'partner', 'internal', 'external', 'other'])],
            'owner_type' => ['required', 'string', Rule::in(['company', 'customer', 'supplier', 'provider', 'employee', 'partner', 'external_party', 'party', 'other'])],
            'owner_id' => ['nullable', 'integer', 'min:1'],
            'party_id' => ['nullable', 'integer', 'min:1'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'ownership_role' => ['required', 'string', Rule::in(['legal_owner', 'registered_owner', 'operational_owner', 'provider', 'current_holder'])],
            'start_date' => [$creating ? 'required' : 'sometimes', 'date'],
            'end_date' => ['nullable', 'date'],
            'is_current' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string'],
            'created_by' => ['nullable', 'integer'],
            'updated_by' => ['nullable', 'integer'],
        ]);

        $data['vehicle_id'] = $vehicleId;
        $data['tenant_id'] = $this->resolveTenantId($request);
        $data['organization_unit_id'] = $this->resolveOrganizationUnitId($request);
        $data['is_current'] = (bool) ($data['is_current'] ?? true);

        return $data;
    }

    private function resolveTenantId(Request $request): int
    {
        $currentTenantId = $this->currentTenant->currentTenantId();
        $requestedTenantId = $request->integer('tenant_id') ?: null;

        if ($currentTenantId !== null && $requestedTenantId !== null && $requestedTenantId !== $currentTenantId) {
            throw ValidationException::withMessages([
                'tenant_id' => ['Tenant scope mismatch for the active request.'],
            ]);
        }

        $tenantId = $currentTenantId ?? $requestedTenantId;
        if ($tenantId === null || $tenantId < 1) {
            throw ValidationException::withMessages([
                'tenant_id' => ['Tenant context is required.'],
            ]);
        }

        return $tenantId;
    }

    private function resolveOrganizationUnitId(Request $request): ?int
    {
        $currentOrganizationUnitId = $this->currentOrganizationUnit->currentOrganizationUnitId();
        $requestedOrganizationUnitId = $request->integer('organization_unit_id') ?: null;

        if (
            $currentOrganizationUnitId !== null
            && $requestedOrganizationUnitId !== null
            && $requestedOrganizationUnitId !== $currentOrganizationUnitId
        ) {
            throw ValidationException::withMessages([
                'organization_unit_id' => ['Organization unit scope mismatch for the active request.'],
            ]);
        }

        return $currentOrganizationUnitId ?? $requestedOrganizationUnitId;
    }

    private function toResponse(Result $result, int $successStatus = 200): JsonResponse
    {
        if ($result->isSuccess()) {
            return response()->json(['data' => $this->normalizePayload($result->value())], $successStatus);
        }

        $error = $result->errorOrFail();
        $status = $error->code === VehicleErrorCode::NOT_FOUND ? 404 : 422;

        return \api_error_response(
            message: $error->message,
            code: $error->code,
            type: 'domain',
            status: $status,
            details: $error->context,
        );
    }

    private function normalizePayload(mixed $payload): mixed
    {
        if ($payload instanceof DataRecord) {
            return $payload->toArray();
        }

        if (is_array($payload)) {
            return array_map(
                fn (mixed $value): mixed => $value instanceof DataRecord ? $value->toArray() : $value,
                $payload,
            );
        }

        return $payload;
    }
}
