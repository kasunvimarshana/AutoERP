<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Inventory\Application\Repositories\TransferOrderLineRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\TransferOrderLineModel;

final class EloquentTransferOrderLineRepository extends EloquentRepository implements TransferOrderLineRepositoryInterface
{
    public function __construct(TransferOrderLineModel $model)
    {
        parent::__construct($model);
    }
}