<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Pricing\Application\Repositories\DiscountRuleRepositoryInterface;
use Modules\Pricing\Infrastructure\Persistence\Eloquent\Models\DiscountRuleModel;

final class EloquentDiscountRuleRepository extends EloquentRepository implements DiscountRuleRepositoryInterface
{
    public function __construct(DiscountRuleModel $model)
    {
        parent::__construct($model);
    }
}
