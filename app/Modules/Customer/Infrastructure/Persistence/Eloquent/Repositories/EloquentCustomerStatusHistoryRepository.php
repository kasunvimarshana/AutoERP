<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Customer\Application\Repositories\CustomerStatusHistoryRepositoryInterface;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerStatusHistoryModel;

final class EloquentCustomerStatusHistoryRepository extends EloquentRepository implements CustomerStatusHistoryRepositoryInterface
{
    public function __construct(CustomerStatusHistoryModel $model)
    {
        parent::__construct($model);
    }
}