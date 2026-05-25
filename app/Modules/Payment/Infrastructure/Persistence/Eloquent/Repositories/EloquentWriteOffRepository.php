<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Payment\Application\Repositories\WriteOffRepositoryInterface;
use Modules\Payment\Infrastructure\Persistence\Eloquent\Models\WriteOffModel;

final class EloquentWriteOffRepository extends EloquentRepository implements WriteOffRepositoryInterface
{
    public function __construct(WriteOffModel $model)
    {
        parent::__construct($model);
    }
}