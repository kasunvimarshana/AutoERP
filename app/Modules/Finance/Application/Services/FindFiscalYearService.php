<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Finance\Application\Contracts\FindFiscalYearServiceInterface;
use Modules\Finance\Domain\Entities\FiscalYear;
use Modules\Finance\Domain\RepositoryInterfaces\FiscalYearRepositoryInterface;

class FindFiscalYearService implements FindFiscalYearServiceInterface
{
    /** @var array<string> */
    private const ALLOWED_FILTERS = ['tenant_id', 'name', 'status'];

    /** @var array<string> */
    private const ALLOWED_SORTS = ['id', 'name', 'start_date', 'end_date', 'status', 'created_at', 'updated_at'];

    public function __construct(private readonly FiscalYearRepositoryInterface $fiscalYearRepository) {}

    public function find(mixed $id): ?FiscalYear
    {
        return $this->fiscalYearRepository->find($id);
    }

    public function list(array $filters = [], ?int $perPage = null, int $page = 1, ?string $sort = null, ?string $include = null): LengthAwarePaginator
    {
        $repository = $this->fiscalYearRepository->resetCriteria();

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
