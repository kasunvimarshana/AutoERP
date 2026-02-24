<?php

namespace Modules\SubscriptionBilling\Infrastructure\Repositories;

use Modules\SubscriptionBilling\Domain\Contracts\SubscriptionPlanRepositoryInterface;
use Modules\SubscriptionBilling\Infrastructure\Models\SubscriptionPlanModel;
use Modules\Shared\Infrastructure\Repositories\BaseEloquentRepository;

class SubscriptionPlanRepository extends BaseEloquentRepository implements SubscriptionPlanRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new SubscriptionPlanModel());
    }

    public function findByCode(string $tenantId, string $code): ?object
    {
        return SubscriptionPlanModel::where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();
    }
}
