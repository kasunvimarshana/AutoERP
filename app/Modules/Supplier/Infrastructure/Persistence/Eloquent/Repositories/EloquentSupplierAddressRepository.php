<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Supplier\Application\Repositories\SupplierAddressRepositoryInterface;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierAddressModel;

final class EloquentSupplierAddressRepository extends EloquentRepository implements SupplierAddressRepositoryInterface
{
    public function __construct(SupplierAddressModel $model)
    {
        parent::__construct($model);
    }
}