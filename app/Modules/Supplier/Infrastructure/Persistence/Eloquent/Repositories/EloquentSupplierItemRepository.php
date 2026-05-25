<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Supplier\Application\Repositories\SupplierItemRepositoryInterface;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierItemModel;

final class EloquentSupplierItemRepository extends EloquentRepository implements SupplierItemRepositoryInterface
{
    public function __construct(SupplierItemModel $model)
    {
        parent::__construct($model);
    }
}