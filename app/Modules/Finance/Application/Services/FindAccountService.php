<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Finance\Application\Contracts\FindAccountServiceInterface;
use Modules\Finance\Domain\Entities\Account;
use Modules\Finance\Domain\RepositoryInterfaces\AccountRepositoryInterface;

class FindAccountService implements FindAccountServiceInterface
{
    /** @var array<string> */
    private const ALLOWED_FILTERS = ['tenant_id', 'parent_id', 'code', 'name', 'type', 'sub_type', 'normal_balance', 'is_active'];

    /** @var array<string> */
    private const ALLOWED_SORTS = ['id', 'code', 'name', 'type', 'created_at', 'updated_at'];

    public function __construct(private readonly AccountRepositoryInterface $accountRepository) {}

    public function find(mixed $id): ?Account
    {
        return $this->accountRepository->find($id);
    }

    public function list(array $filters = [], ?int $perPage = null, int $page = 1, ?string $sort = null): LengthAwarePaginator
    {
        $repository = $this->accountRepository->resetCriteria();

        foreach ($filters as $field => $value) {
            if (! in_array($field, self::ALLOWED_FILTERS, true)) {
                continue;
            }

            if (is_string($value) && in_array($field, ['code', 'name'], true)) {
                $repository->where($field, 'like', '%'.$value.'%');

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
