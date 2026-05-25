<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Customer\Application\Repositories\CustomerAddressRepositoryInterface;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerAddressModel;

final class EloquentCustomerAddressRepository extends EloquentRepository implements CustomerAddressRepositoryInterface
{
    public function __construct(CustomerAddressModel $model)
    {
        parent::__construct($model);
    }
}