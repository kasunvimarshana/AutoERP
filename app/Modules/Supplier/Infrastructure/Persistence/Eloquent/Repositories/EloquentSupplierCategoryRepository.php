<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Supplier\Application\Repositories\SupplierCategoryRepositoryInterface;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierCategoryModel;

final class EloquentSupplierCategoryRepository extends EloquentRepository implements SupplierCategoryRepositoryInterface
{
    public function __construct(SupplierCategoryModel $model)
    {
        parent::__construct($model);
    }
}
