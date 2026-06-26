<?php

declare(strict_types=1);

namespace Modules\User\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;
use Modules\Core\Repositories\EloquentRepository;
use Modules\User\Models\UserModel;

final class EloquentUserRepository extends EloquentRepository implements UserRepositoryInterface
{
    public function __construct(UserModel $model, private readonly TenantExecutionContextInterface $executionContext)
    {
        parent::__construct($model);
    }

    public function countByTenant(int $tenantId): int
    {
        return $this->executionContext->runForTenant($tenantId, fn (): int => $this->query()->where('tenant_id', $tenantId)->count());
    }

    public function lockByIdForTenant(int|string $id, int $tenantId): ?DataRecord
    {
        return $this->executionContext->runForTenant($tenantId, function () use ($id, $tenantId): ?DataRecord {
            $model = $this->query()->where('tenant_id', $tenantId)->whereKey($id)->lockForUpdate()->first();
            return $model instanceof Model ? $this->toRecord($model) : null;
        });
    }

    public function findByTenantAndEmail(int $tenantId, string $email, ?int $excludeId = null, bool $includeDeleted = false): ?DataRecord
    {
        return $this->findByNormalizedKey($tenantId, 'email', strtolower(trim($email)), $excludeId, $includeDeleted);
    }

    public function findByTenantAndUsername(int $tenantId, string $username, ?int $excludeId = null, bool $includeDeleted = false): ?DataRecord
    {
        return $this->findByNormalizedKey($tenantId, 'username', strtolower(trim($username)), $excludeId, $includeDeleted);
    }

    public function findByTenantAndLoginIdentifier(int $tenantId, string $identifier): ?DataRecord
    {
        return $this->executionContext->runForTenant($tenantId, function () use ($tenantId, $identifier): ?DataRecord {
            $normalized = strtolower(trim($identifier));
            $model = $this->query()
                ->where('tenant_id', $tenantId)
                ->where(function (Builder $query) use ($normalized): void {
                    $query->where('email', $normalized)->orWhere('username', $normalized);
                })
                ->first();
            return $model instanceof Model ? $this->toRecord($model) : null;
        });
    }

    public function pageByFilters(
        int $tenantId,
        ?string $search,
        ?string $status,
        ?int $roleId,
        ?int $organizationUnitId,
        int $perPage,
        int $page,
    ): PagedResult {
        return $this->executionContext->runForTenant($tenantId, function () use (
            $tenantId, $search, $status, $roleId, $organizationUnitId, $perPage, $page,
        ): PagedResult {
            $query = $this->query()->where('tenant_id', $tenantId);
            if ($status !== null && trim($status) !== '') {
                $query->where('status', strtolower(trim($status)));
            }
            if ($roleId !== null) {
                $query->whereHas('roles', fn (Builder $roles): Builder => $roles->where('role_id', $roleId));
            }
            if ($organizationUnitId !== null) {
                $query->whereHas('organizationUnitAssignments', fn (Builder $assignments): Builder => $assignments
                    ->where('organization_unit_id', $organizationUnitId)
                    ->where('status', 'active'));
            }
            $term = trim((string) $search);
            if ($term !== '') {
                $prefix = $term.'%';
                $query->where(function (Builder $builder) use ($prefix): void {
                    $builder->where('first_name', 'like', $prefix)
                        ->orWhere('last_name', 'like', $prefix)
                        ->orWhere('username', 'like', $prefix)
                        ->orWhere('email', 'like', $prefix)
                        ->orWhere('phone', 'like', $prefix);
                });
            }
            $paginator = $query->orderBy('first_name')->orderBy('last_name')
                ->paginate(max(1, $perPage), ['*'], 'page', max(1, $page));
            $items = array_values(array_filter(array_map(
                fn (mixed $model): ?DataRecord => $model instanceof Model ? $this->toRecord($model) : null,
                $paginator->items(),
            )));
            return new PagedResult($items, $paginator->total(), $paginator->currentPage(), $paginator->perPage());
        });
    }

    private function findByNormalizedKey(
        int $tenantId,
        string $column,
        string $value,
        ?int $excludeId,
        bool $includeDeleted,
    ): ?DataRecord {
        return $this->executionContext->runForTenant($tenantId, function () use (
            $tenantId, $column, $value, $excludeId, $includeDeleted,
        ): ?DataRecord {
            $query = $this->query()->where('tenant_id', $tenantId)->where($column, $value);
            if ($includeDeleted) {
                $query->withTrashed();
            }
            if ($excludeId !== null) {
                $query->where($query->getModel()->getKeyName(), '!=', $excludeId);
            }
            $model = $query->first();
            return $model instanceof Model ? $this->toRecord($model) : null;
        });
    }
}
