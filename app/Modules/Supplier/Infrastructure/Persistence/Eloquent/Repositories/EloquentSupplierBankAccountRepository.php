<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Supplier\Application\Repositories\SupplierBankAccountRepositoryInterface;
use Modules\Supplier\Infrastructure\Persistence\Eloquent\Models\SupplierBankAccountModel;

final class EloquentSupplierBankAccountRepository extends EloquentRepository implements
    SupplierBankAccountRepositoryInterface
{
    public function __construct(SupplierBankAccountModel $model)
    {
        parent::__construct($model);
    }
}
