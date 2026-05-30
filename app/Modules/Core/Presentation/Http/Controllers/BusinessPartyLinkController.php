<?php

declare(strict_types=1);

namespace Modules\Core\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Modules\Core\Application\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Contracts\Services\BusinessPartyLinkServiceInterface;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Result;

final class BusinessPartyLinkController extends Controller
{
    public function __construct(
        private readonly BusinessPartyLinkServiceInterface $links,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'tenant_id' => ['nullable', 'integer', 'min:1', 'exists:tenants,id'],
            'source_party_type' => ['nullable', 'string', 'max:80'],
            'source_party_id' => ['nullable', 'integer', 'min:1'],
            'target_party_type' => ['nullable', 'string', 'max:80'],
            'target_party_id' => ['nullable', 'integer', 'min:1'],
        ]);
        $filters['tenant_id'] = $this->resolveTenantId($request);

        return $this->toResponse($this->links->list($filters));
    }

    public function store(Request $request): JsonResponse
    {
        return $this->toResponse($this->links->create($this->validated($request)), 201);
    }

    public function update(Request $request, int $businessPartyLink): JsonResponse
    {
        return $this->toResponse($this->links->update($businessPartyLink, $this->validated($request)));
    }

    public function deactivate(Request $request, int $businessPartyLink): JsonResponse
    {
        $data = $request->validate([
            'tenant_id' => ['nullable', 'integer', 'min:1', 'exists:tenants,id'],
            'end_date' => ['nullable', 'date'],
        ]);

        return $this->toResponse($this->links->deactivate(
            $this->resolveTenantId($request),
            $businessPartyLink,
            isset($data['end_date']) ? (string) $data['end_date'] : null,
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'tenant_id' => ['nullable', 'integer', 'min:1', 'exists:tenants,id'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1', 'exists:organization_units,id'],
            'source_party_type' => ['required', 'string', Rule::in(['customer', 'supplier', 'provider', 'employee', 'user', 'company', 'external_party', 'partner', 'party', 'other'])],
            'source_party_id' => ['nullable', 'integer', 'min:1'],
            'source_party_name' => ['nullable', 'string', 'max:255'],
            'target_party_type' => ['required', 'string', Rule::in(['customer', 'supplier', 'provider', 'employee', 'user', 'company', 'external_party', 'partner', 'party', 'other'])],
            'target_party_id' => ['nullable', 'integer', 'min:1'],
            'target_party_name' => ['nullable', 'string', 'max:255'],
            'relation_type' => ['required', 'string', Rule::in(['same_party', 'acts_as', 'billing_relation', 'provider_relation', 'payer_relation', 'payee_relation'])],
            'is_active' => ['sometimes', 'boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'source_context' => ['nullable', 'array'],
            'created_by' => ['nullable', 'integer'],
            'updated_by' => ['nullable', 'integer'],
        ]);

        $data['tenant_id'] = $this->resolveTenantId($request);
        $data['organization_unit_id'] = $this->resolveOrganizationUnitId($request);

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
        $status = $error->code === 'BUSINESS_PARTY_LINK_NOT_FOUND' ? 404 : ($error->code === 'BUSINESS_PARTY_LINK_CONFLICT' ? 409 : 422);

        return \api_error_response($error->code, $error->message, $status, 'domain', $error->context);
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
