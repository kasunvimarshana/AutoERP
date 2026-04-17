<?php

declare(strict_types=1);

namespace Modules\User\Application\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Core\Application\Services\BaseService;
use Modules\User\Application\Contracts\FindUserServiceInterface;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;

/**
 * Read-only service for querying users.
 *
 * Delegates all persistence queries to the repository via the BaseService
 * implementations of find() and list(). The handle() method is intentionally
 * unsupported since this service has no write responsibilities.
 */
class FindUserService extends BaseService implements FindUserServiceInterface
{
    /** @var array<string> */
    private const ALLOWED_FILTERS = ['tenant_id', 'org_unit_id', 'email', 'first_name', 'last_name', 'status'];

    /** @var array<string> */
    private const ALLOWED_SORTS = ['id', 'tenant_id', 'org_unit_id', 'email', 'first_name', 'last_name', 'status', 'created_at', 'updated_at'];

    /** @var array<string> */
    private const ALLOWED_INCLUDES = ['roles', 'permissions', 'attachments', 'devices'];

    public function __construct(private readonly UserRepositoryInterface $userRepository)
    {
        parent::__construct($userRepository);
    }

    public function list(array $filters = [], ?int $perPage = null, int $page = 1, ?string $sort = null, ?string $include = null): LengthAwarePaginator
    {
        $resolvedPerPage = $perPage ?? (int) config('core.pagination.per_page', 15);
        $repository = $this->userRepository->resetCriteria();

        foreach ($filters as $field => $value) {
            if (in_array($field, self::ALLOWED_FILTERS, true)) {
                $repository->where($field, $value);
            }
        }

        foreach ($this->parseIncludes($include) as $relation) {
            $resolvedRelation = $relation === 'permissions' ? 'roles.permissions' : $relation;
            $repository->with($resolvedRelation);
        }

        [$sortField, $sortDirection] = $this->parseSort($sort);
        if ($sortField !== null) {
            $repository->orderBy($sortField, $sortDirection);
        }

        return $repository->paginate($resolvedPerPage, ['*'], 'page', $page);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
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
     * @return array<int, string>
     */
    private function parseIncludes(?string $include): array
    {
        if ($include === null) {
            return [];
        }

        $include = trim($include);
        if ($include === '') {
            return [];
        }

        $relations = array_map('trim', explode(',', $include));

        return array_values(array_unique(array_filter(
            $relations,
            fn (string $relation): bool => in_array($relation, self::ALLOWED_INCLUDES, true)
        )));
    }
}
