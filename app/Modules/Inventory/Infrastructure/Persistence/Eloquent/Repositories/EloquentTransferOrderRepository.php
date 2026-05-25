<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Inventory\Application\Repositories\TransferOrderRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\TransferOrderModel;

final class EloquentTransferOrderRepository extends EloquentRepository implements TransferOrderRepositoryInterface
{
    public function __construct(TransferOrderModel $model)
    {
        parent::__construct($model);
    }
}