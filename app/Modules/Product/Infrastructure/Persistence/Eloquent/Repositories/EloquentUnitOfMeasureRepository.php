<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Product\Domain\RepositoryInterfaces\UnitOfMeasureRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\UnitOfMeasureModel;

class EloquentUnitOfMeasureRepository extends EloquentRepository implements UnitOfMeasureRepositoryInterface
{
    public function __construct(UnitOfMeasureModel $model)
    {
        parent::__construct($model);
    }

    public function findByAbbreviation(string $abbreviation, int $tenantId): mixed
    {
        return $this->model
            ->where('abbreviation', $abbreviation)
            ->where('tenant_id', $tenantId)
            ->first();
    }
}
