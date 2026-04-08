<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Returns\Domain\RepositoryInterfaces\ReturnRepositoryInterface;
use Modules\Returns\Infrastructure\Persistence\Eloquent\Models\ReturnModel;

final class EloquentReturnRepository extends EloquentRepository implements ReturnRepositoryInterface
{
    public function __construct(ReturnModel $model)
    {
        parent::__construct($model);
    }

    public function findByReference(string $reference, int $tenantId): mixed
    {
        return $this->model->newQuery()
            ->where('reference_number', $reference)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    public function findWithLines(int|string $id): mixed
    {
        return $this->model->newQuery()
            ->with('lines')
            ->find($id);
    }
}
