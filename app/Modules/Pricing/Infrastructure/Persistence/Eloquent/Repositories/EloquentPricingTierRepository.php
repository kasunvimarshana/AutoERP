<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Pricing\Application\Repositories\PricingTierRepositoryInterface;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PricingTierModel;

final class EloquentPricingTierRepository extends EloquentRepository implements PricingTierRepositoryInterface
{
    public function __construct(PricingTierModel $model)
    {
        parent::__construct($model);
    }
}
