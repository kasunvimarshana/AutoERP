<?php

declare(strict_types=1);

namespace Modules\Supplier\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Core\Application\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentUserContextAccessorInterface;
use Modules\Supplier\Application\Repositories\SupplierRepositoryInterface;
use Modules\Supplier\Application\Repositories\SupplierTaxProfileRepositoryInterface;
use Modules\Supplier\Presentation\Http\Requests\UpsertSupplierTaxProfileRequest;

final class SupplierTaxProfileController extends Controller
{
    public function __construct(
        private readonly SupplierTaxProfileRepositoryInterface $repository,
        private readonly SupplierRepositoryInterface $suppliers,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
        private readonly CurrentUserContextAccessorInterface $currentUser,
    ) {
    }

    public function show(int|string $supplierId): JsonResponse
    {
        if (! $this->supplierInScope($supplierId)) {
            return response()->json(['message' => 'Supplier not found.'], 404);
        }

        $records = $this->repository->list([
            'tenant_id' => $this->tenantId(),
            'supplier_id' => (int) $supplierId,
        ]);

        $profile = $records[0] ?? null;

        return response()->json(['data' => $profile?->toArray()]);
    }

    public function upsert(UpsertSupplierTaxProfileRequest $request, int|string $supplierId): JsonResponse
    {
        if (! $this->supplierInScope($supplierId)) {
            return response()->json(['message' => 'Supplier not found.'], 404);
        }

        $payload = $request->validated();
        $records = $this->repository->list([
            'tenant_id' => $this->tenantId(),
            'supplier_id' => (int) $supplierId,
        ]);

        $existing = $records[0] ?? null;
        if ($existing === null) {
            $created = $this->repository->create([
                'tenant_id' => $this->tenantId(),
                'organization_unit_id' => $this->currentOrganizationUnit->currentOrganizationUnitId(),
                'metadata' => $payload['metadata'] ?? null,
                'supplier_id' => (int) $supplierId,
                'tax_identifier' => $payload['tax_identifier'] ?? null,
                'vat_identifier' => $payload['vat_identifier'] ?? null,
                'tax_type' => $payload['tax_type'] ?? null,
                'withholding_rate' => $payload['withholding_rate'] ?? null,
                'is_tax_exempt' => (bool) ($payload['is_tax_exempt'] ?? false),
                'tax_exempt_until' => $payload['tax_exempt_until'] ?? null,
                'is_active' => (bool) ($payload['is_active'] ?? true),
                'created_by' => $this->currentUser->currentUserId(),
                'updated_by' => $this->currentUser->currentUserId(),
                'row_version' => 1,
            ]);

            return response()->json(['data' => $created->toArray()], 201);
        }

        $updated = $this->repository->update($existing->id(), [
            'metadata' => $payload['metadata'] ?? $existing->get('metadata'),
            'tax_identifier' => $payload['tax_identifier'] ?? $existing->get('tax_identifier'),
            'vat_identifier' => $payload['vat_identifier'] ?? $existing->get('vat_identifier'),
            'tax_type' => $payload['tax_type'] ?? $existing->get('tax_type'),
            'withholding_rate' => $payload['withholding_rate'] ?? $existing->get('withholding_rate'),
            'is_tax_exempt' => array_key_exists('is_tax_exempt', $payload) ? (bool) $payload['is_tax_exempt'] : (bool) $existing->get('is_tax_exempt'),
            'tax_exempt_until' => $payload['tax_exempt_until'] ?? $existing->get('tax_exempt_until'),
            'is_active' => array_key_exists('is_active', $payload) ? (bool) $payload['is_active'] : (bool) $existing->get('is_active'),
            'updated_by' => $this->currentUser->currentUserId(),
            'row_version' => ((int) $existing->get('row_version', 1)) + 1,
        ]);

        return response()->json(['data' => $updated->toArray()]);
    }

    public function deactivate(int|string $supplierId): JsonResponse
    {
        if (! $this->supplierInScope($supplierId)) {
            return response()->json(['message' => 'Supplier not found.'], 404);
        }

        $records = $this->repository->list([
            'tenant_id' => $this->tenantId(),
            'supplier_id' => (int) $supplierId,
        ]);

        $existing = $records[0] ?? null;
        if ($existing === null) {
            return response()->json(['message' => 'Supplier tax profile not found.'], 404);
        }

        $updated = $this->repository->update($existing->id(), [
            'is_active' => false,
            'updated_by' => $this->currentUser->currentUserId(),
            'row_version' => ((int) $existing->get('row_version', 1)) + 1,
        ]);

        return response()->json(['data' => $updated->toArray()]);
    }

    private function tenantId(): int
    {
        return (int) $this->currentTenant->currentTenantId();
    }

    private function supplierInScope(int|string $supplierId): bool
    {
        $supplier = $this->suppliers->findById($supplierId);

        return $supplier !== null && (int) $supplier->require('tenant_id') === $this->tenantId();
    }
}
