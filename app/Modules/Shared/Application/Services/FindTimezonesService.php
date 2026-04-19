<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Shared\Application\Contracts\FindTimezonesServiceInterface;
use Modules\Shared\Domain\Entities\Timezone;
use Modules\Shared\Domain\RepositoryInterfaces\TimezoneRepositoryInterface;

class FindTimezonesService implements FindTimezonesServiceInterface
{
    private const ALLOWED_FILTERS = ['name'];

    private const ALLOWED_SORTS = ['id', 'name', 'offset', 'created_at'];

    public function __construct(
        private readonly TimezoneRepositoryInterface $repository
    ) {}

    public function find(int $id): ?Timezone
    {
        return $this->repository->find($id);
    }

    public function findByName(string $name): ?Timezone
    {
        return $this->repository->findByName($name);
    }

    public function list(array $filters, int $perPage, int $page, ?string $sort = null): LengthAwarePaginator
    {
        $repository = $this->repository->resetCriteria();

        foreach ($filters as $field => $value) {
            if (in_array($field, self::ALLOWED_FILTERS, true)) {
                $repository->where($field, $value);
            }
        }

        [$sortField, $sortDirection] = $this->parseSort($sort);
        if ($sortField !== null) {
            $repository->orderBy($sortField, $sortDirection);
        }

        return $repository->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * @return array{0: string|null, 1: string}
     */
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
