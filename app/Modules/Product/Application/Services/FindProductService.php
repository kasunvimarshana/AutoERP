<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Product\Application\Contracts\FindProductServiceInterface;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Domain\RepositoryInterfaces\ProductRepositoryInterface;

class FindProductService implements FindProductServiceInterface
{
    /** @var array<string> */
    private const ALLOWED_FILTERS = [
        'tenant_id',
        'category_id',
        'brand_id',
        'org_unit_id',
        'type',
        'name',
        'slug',
        'sku',
        'is_active',
    ];

    /** @var array<string> */
    private const ALLOWED_SORTS = ['id', 'name', 'slug', 'sku', 'type', 'created_at', 'updated_at'];

    /** @var array<string> */
    private const ALLOWED_INCLUDES = [
        'category',
        'brand',
        'baseUom',
        'purchaseUom',
        'salesUom',
        'variants',
    ];

    public function __construct(
        private readonly ProductRepositoryInterface $productRepository
    ) {}

    public function find(mixed $id): ?Product
    {
        return $this->productRepository->find($id);
    }

    public function list(array $filters = [], ?int $perPage = null, int $page = 1, ?string $sort = null, ?string $include = null): LengthAwarePaginator
    {
        $repository = $this->productRepository->resetCriteria();

        foreach ($filters as $field => $value) {
            if (! in_array($field, self::ALLOWED_FILTERS, true)) {
                continue;
            }

            if (is_string($value) && $field === 'name') {
                $repository->where('name', 'like', '%'.$value.'%');

                continue;
            }

            $repository->where($field, $value);
        }

        [$sortField, $sortDirection] = $this->parseSort($sort);
        if ($sortField !== null) {
            $repository->orderBy($sortField, $sortDirection);
        }

        $relations = $this->parseIncludes($include);
        if ($relations !== []) {
            $repository->with($relations);
        }

        $perPage = $perPage ?? 15;

        return $repository->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * @return array{0: string|null, 1: string}
     */
    private function parseSort(?string $sort): array
    {
        if ($sort === null) {
            return [null, 'asc'];
        }

        $sort = trim($sort);

        if ($sort === '') {
            return [null, 'asc'];
        }

        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim($sort, '-');

        if (! in_array($field, self::ALLOWED_SORTS, true)) {
            return [null, 'asc'];
        }

        return [$field, $direction];
    }

    /**
     * @return list<string>
     */
    private function parseIncludes(?string $include): array
    {
        if ($include === null) {
            return [];
        }

        $relations = array_filter(array_map('trim', explode(',', $include)));

        if ($relations === []) {
            return [];
        }

        return array_values(array_filter(
            $relations,
            fn (string $relation): bool => in_array($relation, self::ALLOWED_INCLUDES, true)
        ));
    }
}
