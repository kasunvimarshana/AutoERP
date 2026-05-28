<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Customer\Application\Repositories\CustomerCreditProfileRepositoryInterface;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerCreditProfileModel;

final class EloquentCustomerCreditProfileRepository extends EloquentRepository implements CustomerCreditProfileRepositoryInterface
{
    public function __construct(CustomerCreditProfileModel $model)
    {
        parent::__construct($model);
    }
}
