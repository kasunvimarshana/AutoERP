<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Product\Application\Contracts\FindProductSupplierPriceServiceInterface;
use Modules\Product\Domain\Entities\ProductSupplierPrice;
use Modules\Product\Domain\RepositoryInterfaces\ProductSupplierPriceRepositoryInterface;

class FindProductSupplierPriceService implements FindProductSupplierPriceServiceInterface
{
    private const ALLOWED_FILTERS = ['tenant_id', 'product_id', 'variant_id', 'supplier_id', 'is_preferred', 'is_active'];

    private const ALLOWED_SORTS = ['id', 'unit_price', 'lead_time_days', 'effective_from', 'effective_to', 'created_at', 'updated_at'];

    public function __construct(private readonly ProductSupplierPriceRepositoryInterface $productSupplierPriceRepository) {}

    public function find(mixed $id): ?ProductSupplierPrice
    {
        return $this->productSupplierPriceRepository->find($id);
    }

    public function list(array $filters = [], ?int $perPage = null, int $page = 1, ?string $sort = null, ?string $include = null): LengthAwarePaginator
    {
        $repository = $this->productSupplierPriceRepository->resetCriteria();

        foreach ($filters as $field => $value) {
            if (! in_array($field, self::ALLOWED_FILTERS, true)) {
                continue;
            }
            $repository->where($field, $value);
        }

        [$sortField, $sortDirection] = $this->parseSort($sort);
        if ($sortField !== null) {
            $repository->orderBy($sortField, $sortDirection);
        }

        return $repository->paginate($perPage ?? 15, ['*'], 'page', $page);
    }

    /** @return array{0: ?string, 1: string} */
    private function parseSort(?string $sort): array
    {
        if ($sort === null || trim($sort) === '') {
            return [null, 'asc'];
        }
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');
        if (! in_array($field, self::ALLOWED_SORTS, true)) {
            return [null, 'asc'];
        }

        return [$field, $direction];
    }
}
