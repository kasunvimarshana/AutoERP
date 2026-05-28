<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Pricing\Application\Repositories\PricingRuleRepositoryInterface;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PricingRuleModel;

final class EloquentPricingRuleRepository extends EloquentRepository implements PricingRuleRepositoryInterface
{
    public function __construct(PricingRuleModel $model)
    {
        parent::__construct($model);
    }
}
