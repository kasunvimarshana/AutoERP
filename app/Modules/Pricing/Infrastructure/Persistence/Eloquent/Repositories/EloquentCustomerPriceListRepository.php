<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Pricing\Application\Repositories\CustomerPriceListRepositoryInterface;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\CustomerPriceListModel;

final class EloquentCustomerPriceListRepository extends EloquentRepository implements CustomerPriceListRepositoryInterface
{
    public function __construct(CustomerPriceListModel $model)
    {
        parent::__construct($model);
    }
}