<?php

declare(strict_types=1);

namespace Modules\User\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\DTOs\PagedResult;
use Modules\Core\Repositories\EloquentRepository;
use Modules\User\Models\UserModel;

final class EloquentUserRepository extends EloquentRepository implements UserRepositoryInterface
{
    public function __construct(UserModel $model)
    {
        parent::__construct($model);
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

    public function pageByFilters(?int $tenantId, ?string $search, int $perPage, int $page): PagedResult
    {
        $query = $this->query();

        $this->applyTenantScope($query, $tenantId);

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

        $paginator = $query->paginate(max(1, $perPage), ['*'], 'page', max(1, $page));

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
