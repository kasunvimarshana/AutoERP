<?php

declare(strict_types=1);

namespace Modules\Extension\Repositories;

use Modules\Core\Repositories\EloquentRepository;
use Modules\Extension\Models\EntityAttributeModel;

final class EloquentEntityAttributeRepository extends EloquentRepository implements EntityAttributeRepositoryInterface
{
    public function __construct(EntityAttributeModel $model)
    {
        parent::__construct($model);
    }
}
