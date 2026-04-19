<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Configuration\Application\Contracts\FindCurrenciesServiceInterface;
use Modules\Configuration\Domain\Entities\Currency;
use Modules\Configuration\Domain\RepositoryInterfaces\CurrencyRepositoryInterface;

class FindCurrenciesService implements FindCurrenciesServiceInterface
{
    private const ALLOWED_FILTERS = ['code', 'name', 'is_active'];

    private const ALLOWED_SORTS = ['id', 'code', 'name', 'created_at'];

    public function __construct(
        private readonly CurrencyRepositoryInterface $repository
    ) {}

    public function find(int $id): ?Currency
    {
        return $this->repository->find($id);
    }

    public function findByCode(string $code): ?Currency
    {
        return $this->repository->findByCode($code);
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
