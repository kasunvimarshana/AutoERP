<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Product\Application\Contracts\FindComboItemServiceInterface;
use Modules\Product\Domain\Entities\ComboItem;
use Modules\Product\Domain\RepositoryInterfaces\ComboItemRepositoryInterface;

class FindComboItemService implements FindComboItemServiceInterface
{
    /** @var array<string> */
    private const ALLOWED_FILTERS = ['tenant_id', 'combo_product_id', 'component_product_id', 'component_variant_id', 'uom_id'];

    /** @var array<string> */
    private const ALLOWED_SORTS = ['id', 'combo_product_id', 'component_product_id', 'created_at', 'updated_at'];

    public function __construct(private readonly ComboItemRepositoryInterface $repository) {}

    public function find(mixed $id): ?ComboItem
    {
        return $this->repository->find($id);
    }

    public function list(array $filters = [], ?int $perPage = null, int $page = 1, ?string $sort = null, ?string $include = null): LengthAwarePaginator
    {
        $repo = $this->repository->resetCriteria();

        foreach ($filters as $field => $value) {
            if (! in_array($field, self::ALLOWED_FILTERS, true)) {
                continue;
            }

            $repo->where($field, $value);
        }

        [$sortField, $sortDirection] = $this->parseSort($sort);
        if ($sortField !== null) {
            $repo->orderBy($sortField, $sortDirection);
        }

        return $repo->paginate($perPage ?? 15, ['*'], 'page', $page);
    }

    /**
     * @return array{0:string|null,1:string}
     */
    private function parseSort(?string $sort): array
    {
        if ($sort === null || trim($sort) === '') {
            return [null, 'asc'];
        }

        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $field = ltrim(trim($sort), '-');

        if (! in_array($field, self::ALLOWED_SORTS, true)) {
            return [null, 'asc'];
        }

        return [$field, $direction];
    }
}
