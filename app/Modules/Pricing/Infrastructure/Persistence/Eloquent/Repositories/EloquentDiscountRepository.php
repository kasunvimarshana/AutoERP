<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Pricing\Application\Repositories\DiscountRepositoryInterface;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\DiscountModel;

final class EloquentDiscountRepository extends EloquentRepository implements DiscountRepositoryInterface
{
    public function __construct(DiscountModel $model)
    {
        parent::__construct($model);
    }
}
