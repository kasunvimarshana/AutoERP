<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Services;

use Modules\Pricing\Application\Contracts\FindSupplierPriceListServiceInterface;
use Modules\Pricing\Domain\Entities\SupplierPriceList;
use Modules\Pricing\Domain\RepositoryInterfaces\SupplierPriceListRepositoryInterface;

class FindSupplierPriceListService implements FindSupplierPriceListServiceInterface
{
    public function __construct(private readonly SupplierPriceListRepositoryInterface $supplierPriceListRepository) {}

    public function find(mixed $id): ?SupplierPriceList
    {
        return $this->supplierPriceListRepository->find((int) $id);
    }

    public function list(
        array $filters = [],
        ?int $perPage = null,
        int $page = 1,
        ?string $sort = null,
        ?string $include = null
    ): mixed {
        $repository = $this->supplierPriceListRepository->resetCriteria();

        foreach ($filters as $field => $value) {
            if (in_array($field, ['tenant_id', 'supplier_id', 'price_list_id'], true)) {
                $repository->where($field, $value);
            }
        }

        if (is_string($sort) && $sort !== '') {
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $field = ltrim($sort, '-');
            if (in_array($field, ['id', 'priority', 'created_at'], true)) {
                $repository->orderBy($field, $direction);
            }
        }

        return $repository->paginate($perPage ?? 15, ['*'], 'page', $page);
    }

    public function paginateBySupplier(int $tenantId, int $supplierId, int $perPage = 15, int $page = 1): mixed
    {
        return $this->supplierPriceListRepository
            ->resetCriteria()
            ->where('tenant_id', $tenantId)
            ->where('supplier_id', $supplierId)
            ->orderBy('priority', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
