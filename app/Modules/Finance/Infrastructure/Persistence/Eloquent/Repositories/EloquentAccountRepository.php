<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Finance\Application\Repositories\AccountRepositoryInterface;
use Modules\Finance\Infrastructure\Persistence\Eloquent\Models\AccountModel;

final class EloquentAccountRepository extends FinanceRepository implements AccountRepositoryInterface
{
    public function __construct(AccountModel $model)
    {
        parent::__construct($model);
    }

    public function findPostableById(int|string $id, int $tenantId): ?DataRecord
    {
        $model = $this->query()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('allows_manual_posting', true)
            ->first();

        return $model !== null ? $this->toRecord($model) : null;
    }
}
