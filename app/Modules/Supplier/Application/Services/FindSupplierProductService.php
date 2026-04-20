<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\Services;

use Modules\Supplier\Application\Contracts\FindSupplierProductServiceInterface;
use Modules\Supplier\Domain\Entities\SupplierProduct;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierProductRepositoryInterface;

class FindSupplierProductService implements FindSupplierProductServiceInterface
{
    public function __construct(private readonly SupplierProductRepositoryInterface $supplierProductRepository) {}

    public function find(mixed $id): ?SupplierProduct
    {
        return $this->supplierProductRepository->find((int) $id);
    }

    public function list(
        array $filters = [],
        ?int $perPage = null,
        int $page = 1,
        ?string $sort = null,
        ?string $include = null
    ): mixed {
        $repository = $this->supplierProductRepository->resetCriteria();

        foreach ($filters as $field => $value) {
            if (in_array($field, ['tenant_id', 'supplier_id', 'product_id', 'variant_id', 'is_preferred'], true)) {
                $repository->where($field, $value);
            }
        }

        if (is_string($sort) && $sort !== '') {
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $field = ltrim($sort, '-');
            if (in_array($field, ['id', 'lead_time_days', 'min_order_qty', 'created_at'], true)) {
                $repository->orderBy($field, $direction);
            }
        }

        return $repository->paginate($perPage ?? 15, ['*'], 'page', $page);
    }

    public function paginateBySupplier(int $tenantId, int $supplierId, int $perPage = 15, int $page = 1): mixed
    {
        return $this->supplierProductRepository
            ->resetCriteria()
            ->where('tenant_id', $tenantId)
            ->where('supplier_id', $supplierId)
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
