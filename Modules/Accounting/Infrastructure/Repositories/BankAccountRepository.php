<?php

namespace Modules\Accounting\Infrastructure\Repositories;

use Modules\Accounting\Domain\Contracts\BankAccountRepositoryInterface;
use Modules\Accounting\Infrastructure\Models\BankAccountModel;
use Modules\Shared\Infrastructure\Repositories\BaseEloquentRepository;

class BankAccountRepository extends BaseEloquentRepository implements BankAccountRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new BankAccountModel());
    }

    public function paginate(array $filters = [], int $perPage = 15): object
    {
        $query = BankAccountModel::query();

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }
        if (! empty($filters['currency'])) {
            $query->where('currency', $filters['currency']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function findActiveByTenant(string $tenantId): array
    {
        return BankAccountModel::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->all();
    }
}
