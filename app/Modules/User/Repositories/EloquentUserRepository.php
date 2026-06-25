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
    public function __construct(
        UserModel $model,
        private readonly TenantExecutionContextInterface $executionContext,
    ) {
        parent::__construct($model);
    }

    public function findActivePlatformOperatorCredentials(string $email): ?DataRecord
    {
        return $this->executionContext->runAsControlPlane(function () use ($email): ?DataRecord {
            $model = $this->query()
                ->whereNull('tenant_id')
                ->where('is_platform_operator', true)
                ->where('status', 'active')
                ->where('platform_login_email', strtolower(trim($email)))
                ->first();

            if (! $model instanceof UserModel) {
                return null;
            }

            return new DataRecord([
                'id' => (int) $model->getKey(),
                'first_name' => $model->getAttribute('first_name'),
                'last_name' => $model->getAttribute('last_name'),
                'email' => $model->getAttribute('platform_login_email'),
                'password_hash' => $model->getAttribute('password'),
                'status' => $model->getAttribute('status'),
                'is_platform_operator' => true,
            ]);
        });
    }

    public function countByTenant(int $tenantId): int
    {
        return $this->query()->where('tenant_id', $tenantId)->count();
    }

    public function lockByIdForTenant(int|string $id, int $tenantId): ?DataRecord
    {
        $model = $this->query()
            ->where('tenant_id', $tenantId)
            ->whereKey($id)
            ->lockForUpdate()
            ->first();

        return $model instanceof Model ? $this->toRecord($model) : null;
    }

    public function findByTenantAndEmail(?int $tenantId, string $email, ?int $excludeId = null): ?DataRecord
    {
        $query = $this->query()->where('email', strtolower(trim($email)));

        $this->applyTenantScope($query, $tenantId);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        $model = $query->first();

        return $model instanceof Model ? new DataRecord($model->getAttributes()) : null;
    }

    public function findByTenantAndUsername(?int $tenantId, string $username, ?int $excludeId = null): ?DataRecord
    {
        $query = $this->query()->where('username', strtolower(trim($username)));

        $this->applyTenantScope($query, $tenantId);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        $model = $query->first();

        return $model instanceof Model ? new DataRecord($model->getAttributes()) : null;
    }

    public function findByTenantAndLoginIdentifier(int $tenantId, string $identifier): ?DataRecord
    {
        $normalized = strtolower(trim($identifier));
        $model = $this->query()
            ->where('tenant_id', $tenantId)
            ->where(function (Builder $query) use ($normalized): void {
                $query->where('email', $normalized)
                    ->orWhere('username', $normalized);
            })
            ->first();

        return $model instanceof Model ? new DataRecord($model->getAttributes()) : null;
    }

    public function pageByFilters(
        ?int $tenantId,
        ?string $search,
        ?string $status,
        ?int $roleId,
        ?int $organizationUnitId,
        int $perPage,
        int $page,
    ): PagedResult {
        $query = $this->query();

        $this->applyTenantScope($query, $tenantId);

        if ($status !== null && trim($status) !== '') {
            $query->where('status', trim(strtolower($status)));
        }

        if ($roleId !== null) {
            $query->whereExists(function ($subquery) use ($roleId, $tenantId): void {
                $subquery->selectRaw('1')
                    ->from('user_roles')
                    ->whereColumn('user_roles.user_id', 'users.id')
                    ->where('user_roles.role_id', $roleId);

                if ($tenantId === null) {
                    $subquery->whereNull('user_roles.tenant_id');
                } else {
                    $subquery->where('user_roles.tenant_id', $tenantId);
                }
            });
        }

        if ($organizationUnitId !== null) {
            $query->whereExists(function ($subquery) use ($organizationUnitId, $tenantId): void {
                $subquery->selectRaw('1')
                    ->from('user_organization_units')
                    ->whereColumn('user_organization_units.user_id', 'users.id')
                    ->where('user_organization_units.organization_unit_id', $organizationUnitId)
                    ->where('user_organization_units.status', 'active');

                if ($tenantId !== null) {
                    $subquery->where('user_organization_units.tenant_id', $tenantId);
                }
            });
        }

        if ($search !== null && trim($search) !== '') {
            $term = trim($search);
            $query->where(function (Builder $builder) use ($term): void {
                $builder->where('first_name', 'like', '%'.$term.'%')
                    ->orWhere('last_name', 'like', '%'.$term.'%')
                    ->orWhere('username', 'like', '%'.$term.'%')
                    ->orWhere('email', 'like', '%'.$term.'%')
                    ->orWhere('phone', 'like', '%'.$term.'%');
            });
        }

        $paginator = $query
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate(max(1, $perPage), ['*'], 'page', max(1, $page));

        $items = [];
        foreach ($paginator->items() as $model) {
            if ($model instanceof Model) {
                $items[] = $this->toRecord($model);
            }
        }

        return new PagedResult($items, $paginator->total(), $paginator->currentPage(), $paginator->perPage());
    }

    public function findByTenantAndIdentityReference(
        int $tenantId,
        string $providerKey,
        string $providerUserKey,
    ): ?DataRecord {
        $query = $this->query()->where('tenant_id', $tenantId);
        $query->where('metadata->identity_references->'.$providerKey, $providerUserKey);

        $model = $query->first();

        return $model instanceof Model ? $this->toRecord($model) : null;
    }

    private function applyTenantScope(Builder $query, ?int $tenantId): void
    {
        if ($tenantId === null) {
            $query->whereNull('tenant_id');

            return;
        }

        $query->where('tenant_id', $tenantId);
    }
}
