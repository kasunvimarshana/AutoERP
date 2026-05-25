<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Inventory\Application\Repositories\SerialRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\SerialModel;

final class EloquentSerialRepository extends EloquentRepository implements SerialRepositoryInterface
{
    public function __construct(SerialModel $model)
    {
        parent::__construct($model);
    }
}