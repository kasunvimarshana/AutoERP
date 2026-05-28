<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Pricing\Application\Repositories\PriceHistoryRepositoryInterface;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PriceHistoryModel;

final class EloquentPriceHistoryRepository extends EloquentRepository implements PriceHistoryRepositoryInterface
{
    public function __construct(PriceHistoryModel $model)
    {
        parent::__construct($model);
    }
}
