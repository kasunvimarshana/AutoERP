<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Product\Application\Contracts\FindAttributeGroupServiceInterface;
use Modules\Product\Domain\Entities\AttributeGroup;
use Modules\Product\Domain\RepositoryInterfaces\AttributeGroupRepositoryInterface;

class FindAttributeGroupService implements FindAttributeGroupServiceInterface
{
    private const ALLOWED_FILTERS = ['tenant_id', 'name', 'code', 'is_active'];

    private const ALLOWED_SORTS = ['id', 'name', 'code', 'sort_order', 'created_at', 'updated_at'];

    public function __construct(private readonly AttributeGroupRepositoryInterface $attributeGroupRepository) {}

    public function find(mixed $id): ?AttributeGroup
    {
        return $this->attributeGroupRepository->find($id);
    }

    public function list(array $filters = [], ?int $perPage = null, int $page = 1, ?string $sort = null, ?string $include = null): LengthAwarePaginator
    {
        $repository = $this->attributeGroupRepository->resetCriteria();

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
