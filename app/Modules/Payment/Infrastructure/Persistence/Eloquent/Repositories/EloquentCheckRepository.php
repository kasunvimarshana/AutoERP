<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Payment\Application\Repositories\CheckRepositoryInterface;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\CheckModel;

final class EloquentCheckRepository extends EloquentRepository implements CheckRepositoryInterface
{
    public function __construct(CheckModel $model)
    {
        parent::__construct($model);
    }
}