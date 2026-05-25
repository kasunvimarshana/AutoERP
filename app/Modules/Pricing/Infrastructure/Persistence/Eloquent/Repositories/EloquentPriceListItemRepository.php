<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Pricing\Application\Repositories\PriceListItemRepositoryInterface;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PriceListItemModel;

final class EloquentPriceListItemRepository extends EloquentRepository implements PriceListItemRepositoryInterface
{
    public function __construct(PriceListItemModel $model)
    {
        parent::__construct($model);
    }
}