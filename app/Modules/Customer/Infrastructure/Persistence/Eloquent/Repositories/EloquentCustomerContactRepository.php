<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Customer\Application\Repositories\CustomerContactRepositoryInterface;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerContactModel;

final class EloquentCustomerContactRepository extends EloquentRepository implements CustomerContactRepositoryInterface
{
    public function __construct(CustomerContactModel $model)
    {
        parent::__construct($model);
    }
}