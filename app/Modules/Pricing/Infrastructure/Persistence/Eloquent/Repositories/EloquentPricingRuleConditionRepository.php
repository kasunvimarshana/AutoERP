<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Pricing\Application\Repositories\PricingRuleConditionRepositoryInterface;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\PricingRuleConditionModel;

final class EloquentPricingRuleConditionRepository extends EloquentRepository implements PricingRuleConditionRepositoryInterface
{
    public function __construct(PricingRuleConditionModel $model)
    {
        parent::__construct($model);
    }
}
