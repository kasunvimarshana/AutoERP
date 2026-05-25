<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Supplier\Application\Repositories\SupplierRepositoryInterface;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierModel;

final class EloquentSupplierRepository extends EloquentRepository implements SupplierRepositoryInterface
{
    public function __construct(SupplierModel $model)
    {
        parent::__construct($model);
    }
}