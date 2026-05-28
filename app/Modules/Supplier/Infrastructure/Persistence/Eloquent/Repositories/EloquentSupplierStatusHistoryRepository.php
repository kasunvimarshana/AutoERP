<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Supplier\Application\Repositories\SupplierStatusHistoryRepositoryInterface;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierStatusHistoryModel;

final class EloquentSupplierStatusHistoryRepository extends EloquentRepository implements
    SupplierStatusHistoryRepositoryInterface
{
    public function __construct(SupplierStatusHistoryModel $model)
    {
        parent::__construct($model);
    }
}
