<?php

declare(strict_types=1);

namespace Modules\Purchase\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Purchase\Application\Repositories\GrnHeaderRepositoryInterface;
use Modules\Purchase\Infrastructure\Persistence\Eloquent\Models\GrnHeaderModel;

final class EloquentGrnHeaderRepository extends EloquentRepository implements GrnHeaderRepositoryInterface
{
    public function __construct(GrnHeaderModel $model)
    {
        parent::__construct($model);
    }
}