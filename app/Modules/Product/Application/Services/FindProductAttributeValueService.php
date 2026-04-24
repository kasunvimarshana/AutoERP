<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Product\Application\Contracts\FindProductAttributeValueServiceInterface;
use Modules\Product\Domain\Entities\ProductAttributeValue;
use Modules\Product\Domain\RepositoryInterfaces\ProductAttributeValueRepositoryInterface;

class FindProductAttributeValueService implements FindProductAttributeValueServiceInterface
{
    /** @var array<string> */
    private const ALLOWED_FILTERS = ['tenant_id', 'attribute_id', 'value'];

    /** @var array<string> */
    private const ALLOWED_SORTS = ['id', 'value', 'sort_order', 'created_at', 'updated_at'];

    public function __construct(private readonly ProductAttributeValueRepositoryInterface $repository) {}

    public function find(mixed $id): ?ProductAttributeValue
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

            if ($field === 'value' && is_string($value)) {
                $repo->where('value', 'like', '%'.$value.'%');

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
