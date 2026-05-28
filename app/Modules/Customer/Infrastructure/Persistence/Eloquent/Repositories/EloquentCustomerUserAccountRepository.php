<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Customer\Application\Repositories\CustomerUserAccountRepositoryInterface;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerUserAccountModel;

final class EloquentCustomerUserAccountRepository extends EloquentRepository implements CustomerUserAccountRepositoryInterface
{
    public function __construct(CustomerUserAccountModel $model)
    {
        parent::__construct($model);
    }
}