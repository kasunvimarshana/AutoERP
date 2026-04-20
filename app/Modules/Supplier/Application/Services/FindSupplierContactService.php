<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Services;

use Modules\Supplier\Application\Contracts\FindSupplierContactServiceInterface;
use Modules\Supplier\Domain\Entities\SupplierContact;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierContactRepositoryInterface;

class FindSupplierContactService implements FindSupplierContactServiceInterface
{
    public function __construct(private readonly SupplierContactRepositoryInterface $supplierContactRepository) {}

    public function find(mixed $id): ?SupplierContact
    {
        return $this->supplierContactRepository->find((int) $id);
    }

    public function list(
        array $filters = [],
        ?int $perPage = null,
        int $page = 1,
        ?string $sort = null,
        ?string $include = null
    ): mixed {
        $repository = $this->supplierContactRepository->resetCriteria();

        foreach ($filters as $field => $value) {
            if (in_array($field, ['tenant_id', 'supplier_id', 'email', 'is_primary'], true)) {
                $repository->where($field, $value);
            }
        }

        if (is_string($sort) && $sort !== '') {
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $field = ltrim($sort, '-');
            if (in_array($field, ['id', 'name', 'email', 'created_at'], true)) {
                $repository->orderBy($field, $direction);
            }
        }

        return $repository->paginate($perPage ?? 15, ['*'], 'page', $page);
    }

    public function paginateBySupplier(int $tenantId, int $supplierId, int $perPage = 15, int $page = 1): mixed
    {
        return $this->supplierContactRepository
            ->resetCriteria()
            ->where('tenant_id', $tenantId)
            ->where('supplier_id', $supplierId)
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
