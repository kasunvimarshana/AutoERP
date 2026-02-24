<?php

namespace Modules\Accounting\Infrastructure\Repositories;

use Modules\Accounting\Domain\Contracts\AccountRepositoryInterface;
use Modules\Accounting\Infrastructure\Models\AccountModel;
use Modules\Shared\Infrastructure\Repositories\BaseEloquentRepository;

class AccountRepository extends BaseEloquentRepository implements AccountRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new AccountModel());
    }

    public function findByCode(string $tenantId, string $code): ?object
    {
        return AccountModel::where('tenant_id', $tenantId)->where('code', $code)->first();
    }

    public function paginate(array $filters = [], int $perPage = 15): object
    {
        $query = AccountModel::query();

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }
        if (! empty($filters['parent_id'])) {
            $query->where('parent_id', $filters['parent_id']);
        }

        return $query->orderBy('code')->paginate($perPage);
    }
}
