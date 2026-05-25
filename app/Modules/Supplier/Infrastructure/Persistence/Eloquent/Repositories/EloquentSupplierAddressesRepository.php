<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Supplier\Application\Repositories\SupplierAddressesRepositoryInterface;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierAddressesModel;

final class EloquentSupplierAddressesRepository extends EloquentRepository implements SupplierAddressesRepositoryInterface
{
    public function __construct(SupplierAddressesModel $model)
    {
        parent::__construct($model);
    }
}