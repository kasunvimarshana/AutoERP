<?php

declare(strict_types=1);

namespace Modules\Supplier\Presentation\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Core\Application\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentUserContextAccessorInterface;
use Modules\Supplier\Application\Repositories\SupplierBankAccountRepositoryInterface;
use Modules\Supplier\Application\Repositories\SupplierRepositoryInterface;
use Modules\Supplier\Presentation\Http\Requests\UpsertSupplierBankAccountRequest;

final class SupplierBankAccountController extends Controller
{
    public function __construct(
        private readonly SupplierBankAccountRepositoryInterface $repository,
        private readonly SupplierRepositoryInterface $suppliers,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
        private readonly CurrentUserContextAccessorInterface $currentUser,
    ) {}

    public function index(int|string $supplierId): JsonResponse
    {
        if (! $this->supplierInScope($supplierId)) {
            return response()->json(['message' => 'Supplier not found.'], 404);
        }

        $records = $this->repository->list([
            'tenant_id' => $this->tenantId(),
            'supplier_id' => (int) $supplierId,
        ]);

        return response()->json(['data' => array_map(static fn ($item): array => $item->toArray(), $records)]);
    }

    public function store(UpsertSupplierBankAccountRequest $request, int|string $supplierId): JsonResponse
    {
        if (! $this->supplierInScope($supplierId)) {
            return response()->json(['message' => 'Supplier not found.'], 404);
        }

        $payload = $request->validated();
        $record = DB::transaction(function () use ($payload, $supplierId) {
            if ((bool) ($payload['is_primary'] ?? false)) {
                $this->clearPrimary((int) $supplierId);
            }

            return $this->repository->create([
                'tenant_id' => $this->tenantId(),
                'organization_unit_id' => $this->currentOrganizationUnit->currentOrganizationUnitId(),
                'metadata' => $payload['metadata'] ?? null,
                'supplier_id' => (int) $supplierId,
                'account_name' => $payload['account_name'],
                'account_number' => $payload['account_number'],
                'iban' => $payload['iban'] ?? null,
                'swift_code' => $payload['swift_code'] ?? null,
                'bank_name' => $payload['bank_name'],
                'branch_name' => $payload['branch_name'] ?? null,
                'bank_code' => $payload['bank_code'] ?? null,
                'branch_code' => $payload['branch_code'] ?? null,
                'currency_id' => $payload['currency_id'] ?? null,
                'is_primary' => (bool) ($payload['is_primary'] ?? false),
                'is_active' => (bool) ($payload['is_active'] ?? true),
                'created_by' => $this->currentUser->currentUserId(),
                'updated_by' => $this->currentUser->currentUserId(),
                'row_version' => 1,
            ]);
        });

        return response()->json(['data' => $record->toArray()], 201);
    }

    public function update(
        UpsertSupplierBankAccountRequest $request,
        int|string $supplierId,
        int|string $id,
    ): JsonResponse {
        if (! $this->supplierInScope($supplierId)) {
            return response()->json(['message' => 'Supplier not found.'], 404);
        }

        $existing = $this->repository->findById($id);
        if (
            $existing === null
            || (int) $existing->require('tenant_id') !== $this->tenantId()
            || (int) $existing->require('supplier_id') !== (int) $supplierId
        ) {
            return response()->json(['message' => 'Supplier bank account not found.'], 404);
        }

        $payload = $request->validated();
        $record = DB::transaction(function () use ($existing, $id, $payload, $supplierId) {
            if ((bool) ($payload['is_primary'] ?? false)) {
                $this->clearPrimary((int) $supplierId, (int) $id);
            }

            return $this->repository->update($id, [
                'metadata' => $payload['metadata'] ?? $existing->get('metadata'),
                'account_name' => $payload['account_name'] ?? $existing->get('account_name'),
                'account_number' => $payload['account_number'] ?? $existing->get('account_number'),
                'iban' => $payload['iban'] ?? $existing->get('iban'),
                'swift_code' => $payload['swift_code'] ?? $existing->get('swift_code'),
                'bank_name' => $payload['bank_name'] ?? $existing->get('bank_name'),
                'branch_name' => $payload['branch_name'] ?? $existing->get('branch_name'),
                'bank_code' => $payload['bank_code'] ?? $existing->get('bank_code'),
                'branch_code' => $payload['branch_code'] ?? $existing->get('branch_code'),
                'currency_id' => $payload['currency_id'] ?? $existing->get('currency_id'),
                'is_primary' => array_key_exists('is_primary', $payload)
                    ? (bool) $payload['is_primary']
                    : (bool) $existing->get('is_primary'),
                'is_active' => array_key_exists('is_active', $payload)
                    ? (bool) $payload['is_active']
                    : (bool) $existing->get('is_active'),
                'updated_by' => $this->currentUser->currentUserId(),
                'row_version' => ((int) $existing->get('row_version', 1)) + 1,
            ]);
        });

        return response()->json(['data' => $record->toArray()]);
    }

    public function destroy(int|string $supplierId, int|string $id): JsonResponse
    {
        if (! $this->supplierInScope($supplierId)) {
            return response()->json(['message' => 'Supplier not found.'], 404);
        }

        $existing = $this->repository->findById($id);
        if (
            $existing === null
            || (int) $existing->require('tenant_id') !== $this->tenantId()
            || (int) $existing->require('supplier_id') !== (int) $supplierId
        ) {
            return response()->json(['message' => 'Supplier bank account not found.'], 404);
        }

        $this->repository->delete($id);

        return response()->json(null, 204);
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

    private function clearPrimary(int $supplierId, ?int $excludeId = null): void
    {
        $accounts = $this->repository->list([
            'tenant_id' => $this->tenantId(),
            'supplier_id' => $supplierId,
        ]);

        foreach ($accounts as $account) {
            $accountId = (int) $account->id();
            if ($excludeId !== null && $accountId === $excludeId) {
                continue;
            }

            if ((bool) $account->get('is_primary') === false) {
                continue;
            }

            $this->repository->update($accountId, [
                'is_primary' => false,
                'updated_by' => $this->currentUser->currentUserId(),
                'row_version' => ((int) $account->get('row_version', 1)) + 1,
            ]);
        }
    }
}
