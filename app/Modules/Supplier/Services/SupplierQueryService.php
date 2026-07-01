<?php

declare(strict_types=1);

namespace Modules\Supplier\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Modules\Supplier\Enums\SupplierStatus;
use Modules\Supplier\Models\Supplier;

final class SupplierQueryService
{
    public function paginate(array $criteria, int $tenantId, ?int $organizationUnitId, int $perPage): LengthAwarePaginator
    {
        $query = $this->baseQuery($tenantId, $organizationUnitId)->with(['defaultCurrency', 'categories']);
        $this->applyCriteria($query, $criteria);

        $sort = in_array(($criteria['sort'] ?? null), ['supplier_number', 'code', 'name', 'status', 'created_at'], true)
            ? (string) $criteria['sort']
            : 'name';
        $direction = ($criteria['direction'] ?? null) === 'desc' ? 'desc' : 'asc';

        return $query->orderBy($sort, $direction)->paginate($perPage);
    }

    public function lookup(
        array $criteria,
        int $tenantId,
        ?int $organizationUnitId,
        int $perPage,
        string $kind,
    ): LengthAwarePaginator {
        if ($kind !== 'all') {
            $criteria['status'] = SupplierStatus::Active->value;
        }
        if ($kind === 'credit-allowed') {
            $criteria['is_credit_allowed'] = true;
        } elseif ($kind === 'by-item' && empty($criteria['item_id'])) {
            throw new \InvalidArgumentException('Item is required for supplier by-item lookup.');
        }

        return $this->paginate($criteria, $tenantId, $organizationUnitId, min($perPage, 50));
    }

    public function find(int $id, int $tenantId, ?int $organizationUnitId): Supplier
    {
        $supplier = $this->baseQuery($tenantId, $organizationUnitId)
            ->with(['defaultCurrency', 'categories'])
            ->find($id);

        if ($supplier instanceof Supplier) {
            return $supplier;
        }

        $this->assertSupplierIsNotOwnedByAnotherScope($id, $tenantId, $organizationUnitId);
        throw (new ModelNotFoundException())->setModel(Supplier::class, [$id]);
    }

    public function supplier(int $id, int $tenantId, ?int $organizationUnitId): Supplier
    {
        $supplier = $this->baseQuery($tenantId, $organizationUnitId)->find($id);

        if ($supplier instanceof Supplier) {
            return $supplier;
        }

        $this->assertSupplierIsNotOwnedByAnotherScope($id, $tenantId, $organizationUnitId);
        throw (new ModelNotFoundException())->setModel(Supplier::class, [$id]);
    }

    public function delete(Supplier $supplier): void
    {
        $supplier->delete();
    }

    private function baseQuery(int $tenantId, ?int $organizationUnitId): Builder
    {
        return Supplier::query()->forTenant($tenantId, $organizationUnitId);
    }

    private function applyCriteria(Builder $query, array $criteria): void
    {
        $search = trim((string) ($criteria['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $scope) use ($search): void {
                $scope->where('supplier_number', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        foreach (['status', 'supplier_type', 'is_credit_allowed'] as $filter) {
            if (array_key_exists($filter, $criteria) && $criteria[$filter] !== null && $criteria[$filter] !== '') {
                $query->where($filter, $criteria[$filter]);
            }
        }
        if (! empty($criteria['category_id'])) {
            $query->whereHas('categories', fn (Builder $categories): Builder => $categories
                ->whereKey((int) $criteria['category_id']));
        }
        if (! empty($criteria['item_id'])) {
            $query->whereHas('itemMappings', fn (Builder $mappings): Builder => $mappings
                ->where('item_id', (int) $criteria['item_id'])
                ->where('is_active', true));
        }
    }

    private function assertSupplierIsNotOwnedByAnotherScope(int $id, int $tenantId, ?int $organizationUnitId): void
    {
        $record = DB::table('suppliers')
            ->where('id', $id)
            ->whereNull('deleted_at')
            ->first(['tenant_id', 'organization_unit_id']);

        if ($record === null) {
            return;
        }

        if ((int) $record->tenant_id !== $tenantId) {
            throw new AuthorizationException('Supplier belongs to a different tenant.');
        }

        $recordOrganizationUnitId = $record->organization_unit_id === null
            ? null
            : (int) $record->organization_unit_id;

        if ($recordOrganizationUnitId !== $organizationUnitId) {
            throw new AuthorizationException('Supplier belongs to a different organization unit.');
        }
    }
}
