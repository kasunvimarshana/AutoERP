<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\HR\Application\Repositories\DesignationRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\DesignationModel;

final class EloquentDesignationRepository extends EloquentRepository implements DesignationRepositoryInterface
{
    public function __construct(DesignationModel $model)
    {
        parent::__construct($model);
    }
}