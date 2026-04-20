<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Services;

use Modules\Supplier\Application\Contracts\FindSupplierAddressServiceInterface;
use Modules\Supplier\Domain\Entities\SupplierAddress;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierAddressRepositoryInterface;

class FindSupplierAddressService implements FindSupplierAddressServiceInterface
{
    public function __construct(private readonly SupplierAddressRepositoryInterface $supplierAddressRepository) {}

    public function find(mixed $id): ?SupplierAddress
    {
        return $this->supplierAddressRepository->find((int) $id);
    }

    public function list(
        array $filters = [],
        ?int $perPage = null,
        int $page = 1,
        ?string $sort = null,
        ?string $include = null
    ): mixed {
        $repository = $this->supplierAddressRepository->resetCriteria();

        foreach ($filters as $field => $value) {
            if (in_array($field, ['tenant_id', 'supplier_id', 'type', 'country_id', 'is_default'], true)) {
                $repository->where($field, $value);
            }
        }

        if (is_string($sort) && $sort !== '') {
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $field = ltrim($sort, '-');
            if (in_array($field, ['id', 'type', 'city', 'postal_code', 'created_at'], true)) {
                $repository->orderBy($field, $direction);
            }
        }

        return $repository->paginate($perPage ?? 15, ['*'], 'page', $page);
    }

    public function paginateBySupplier(int $tenantId, int $supplierId, int $perPage = 15, int $page = 1): mixed
    {
        return $this->supplierAddressRepository
            ->resetCriteria()
            ->where('tenant_id', $tenantId)
            ->where('supplier_id', $supplierId)
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
