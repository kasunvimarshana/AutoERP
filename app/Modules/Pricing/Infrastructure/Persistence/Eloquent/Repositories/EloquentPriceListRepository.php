<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Pricing\Application\Repositories\PriceListRepositoryInterface;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PriceListModel;

final class EloquentPriceListRepository extends EloquentRepository implements PriceListRepositoryInterface
{
    public function __construct(PriceListModel $model)
    {
        parent::__construct($model);
    }
}