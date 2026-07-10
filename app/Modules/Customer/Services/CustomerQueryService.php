<?php

declare(strict_types=1);

namespace Modules\Customer\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Customer\Enums\CustomerStatus;
use Modules\Customer\Models\Customer;

final class CustomerQueryService
{
    public function paginate(array $criteria, int $tenantId, ?int $organizationUnitId, int $perPage): LengthAwarePaginator
    {
        $query = $this->baseQuery($tenantId, $organizationUnitId)->with(['defaultCurrency', 'categories', 'creditProfile']);
        $this->applyCriteria($query, $criteria);

        $sort = in_array(($criteria['sort'] ?? null), ['customer_number', 'code', 'name', 'status', 'created_at'], true)
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
            $criteria['status'] = CustomerStatus::Active->value;
        }
        if ($kind === 'credit-allowed') {
            $criteria['credit_allowed'] = true;
        }

        return $this->paginate($criteria, $tenantId, $organizationUnitId, min($perPage, 50));
    }

    public function find(int $id, int $tenantId, ?int $organizationUnitId): Customer
    {
        return $this->baseQuery($tenantId, $organizationUnitId)
            ->with(['defaultCurrency', 'categories', 'creditProfile'])
            ->findOrFail($id);
    }

    public function customer(int $id, int $tenantId, ?int $organizationUnitId): Customer
    {
        return $this->baseQuery($tenantId, $organizationUnitId)->findOrFail($id);
    }

    public function delete(Customer $customer): void
    {
        $customer->delete();
    }

    private function baseQuery(int $tenantId, ?int $organizationUnitId): Builder
    {
        return Customer::query()->forTenant($tenantId, $organizationUnitId);
    }

    private function applyCriteria(Builder $query, array $criteria): void
    {
        $search = trim((string) ($criteria['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $scope) use ($search): void {
                $scope->where('customer_number', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        foreach (['status', 'customer_type'] as $filter) {
            if (array_key_exists($filter, $criteria) && $criteria[$filter] !== null && $criteria[$filter] !== '') {
                $query->where($filter, $criteria[$filter]);
            }
        }
        if (array_key_exists('credit_allowed', $criteria) && $criteria['credit_allowed'] !== null && $criteria['credit_allowed'] !== '') {
            $query->whereHas('creditProfile', static fn (Builder $profile): Builder => $profile
                ->where('credit_allowed', (bool) $criteria['credit_allowed'])
                ->where('is_active', true));
        }
        if (! empty($criteria['category_id'])) {
            $query->whereHas('categories', fn (Builder $categories): Builder => $categories
                ->whereKey((int) $criteria['category_id']));
        }
    }
}
