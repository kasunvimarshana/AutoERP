<?php

declare(strict_types=1);

namespace Modules\Extension\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Extension\Application\Repositories\EntityAttributeRepositoryInterface;
use Modules\Extension\Infrastructure\Persistence\Eloquent\Models\EntityAttributeModel;

final class EloquentEntityAttributeRepository extends EloquentRepository implements EntityAttributeRepositoryInterface
{
    public function __construct(EntityAttributeModel $model)
    {
        parent::__construct($model);
    }
}