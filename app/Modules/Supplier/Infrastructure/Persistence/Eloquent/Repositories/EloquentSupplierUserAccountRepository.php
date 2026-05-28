<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Supplier\Application\Repositories\SupplierUserAccountRepositoryInterface;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierUserAccountModel;

final class EloquentSupplierUserAccountRepository extends EloquentRepository implements
    SupplierUserAccountRepositoryInterface
{
    public function __construct(SupplierUserAccountModel $model)
    {
        parent::__construct($model);
    }
}
