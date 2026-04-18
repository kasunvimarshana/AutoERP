<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Product\Application\Contracts\FindProductCategoryServiceInterface;
use Modules\Product\Domain\Entities\ProductCategory;
use Modules\Product\Domain\RepositoryInterfaces\ProductCategoryRepositoryInterface;

class FindProductCategoryService implements FindProductCategoryServiceInterface
{
    /** @var array<string> */
    private const ALLOWED_FILTERS = [
        'tenant_id',
        'parent_id',
        'name',
        'slug',
        'code',
        'is_active',
    ];

    /** @var array<string> */
    private const ALLOWED_SORTS = ['id', 'name', 'slug', 'code', 'depth', 'created_at', 'updated_at'];

    public function __construct(
        private readonly ProductCategoryRepositoryInterface $productCategoryRepository
    ) {}

    public function find(mixed $id): ?ProductCategory
    {
        return $this->productCategoryRepository->find($id);
    }

    public function list(array $filters = [], ?int $perPage = null, int $page = 1, ?string $sort = null, ?string $include = null): LengthAwarePaginator
    {
        $repository = $this->productCategoryRepository->resetCriteria();

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
}
