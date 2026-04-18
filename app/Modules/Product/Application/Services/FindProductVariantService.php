<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Product\Application\Contracts\FindProductVariantServiceInterface;
use Modules\Product\Domain\Entities\ProductVariant;
use Modules\Product\Domain\RepositoryInterfaces\ProductVariantRepositoryInterface;

class FindProductVariantService implements FindProductVariantServiceInterface
{
    /** @var array<string> */
    private const ALLOWED_FILTERS = [
        'product_id',
        'name',
        'sku',
        'is_default',
        'is_active',
    ];

    /** @var array<string> */
    private const ALLOWED_SORTS = ['id', 'name', 'sku', 'is_default', 'is_active', 'created_at', 'updated_at'];

    public function __construct(
        private readonly ProductVariantRepositoryInterface $productVariantRepository
    ) {}

    public function find(mixed $id): ?ProductVariant
    {
        return $this->productVariantRepository->find($id);
    }

    public function list(array $filters = [], ?int $perPage = null, int $page = 1, ?string $sort = null, ?string $include = null): LengthAwarePaginator
    {
        $repository = $this->productVariantRepository->resetCriteria();

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
