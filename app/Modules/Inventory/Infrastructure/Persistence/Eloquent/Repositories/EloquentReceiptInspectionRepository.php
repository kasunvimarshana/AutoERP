<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Inventory\Application\Repositories\ReceiptInspectionRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\ReceiptInspectionModel;

final class EloquentReceiptInspectionRepository extends EloquentRepository implements ReceiptInspectionRepositoryInterface
{
    public function __construct(ReceiptInspectionModel $model)
    {
        parent::__construct($model);
    }
}