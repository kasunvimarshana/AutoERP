<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Supplier\Application\Repositories\SupplierContactRepositoryInterface;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierContactModel;

final class EloquentSupplierContactRepository extends EloquentRepository implements SupplierContactRepositoryInterface
{
    public function __construct(SupplierContactModel $model)
    {
        parent::__construct($model);
    }
}