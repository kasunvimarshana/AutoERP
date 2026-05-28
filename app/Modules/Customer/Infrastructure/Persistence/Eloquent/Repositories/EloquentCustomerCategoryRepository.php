<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Customer\Application\Repositories\CustomerCategoryRepositoryInterface;
use Modules\Customer\Infrastructure\Persistence\Eloquent\Models\CustomerCategoryModel;

final class EloquentCustomerCategoryRepository extends EloquentRepository implements CustomerCategoryRepositoryInterface
{
    public function __construct(CustomerCategoryModel $model)
    {
        parent::__construct($model);
    }
}