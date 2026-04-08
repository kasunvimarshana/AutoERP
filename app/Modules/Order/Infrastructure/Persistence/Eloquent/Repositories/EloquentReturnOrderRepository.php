<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Order\Domain\Contracts\Repositories\ReturnOrderRepositoryInterface;
use Modules\Order\Infrastructure\Persistence\Eloquent\Models\ReturnOrderModel;

class EloquentReturnOrderRepository extends EloquentRepository implements ReturnOrderRepositoryInterface
{
    public function __construct(ReturnOrderModel $model)
    {
        parent::__construct($model);
    }
}
