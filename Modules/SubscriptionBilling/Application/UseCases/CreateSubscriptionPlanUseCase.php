<?php

namespace Modules\SubscriptionBilling\Application\UseCases;

use Illuminate\Support\Facades\DB;
use Modules\SubscriptionBilling\Domain\Contracts\SubscriptionPlanRepositoryInterface;

class CreateSubscriptionPlanUseCase
{
    public function __construct(
        private SubscriptionPlanRepositoryInterface $repo,
    ) {}

    public function execute(array $data): object
    {
        return DB::transaction(function () use ($data) {
            $tenantId = $data['tenant_id'] ?? null;

            return $this->repo->create(array_merge($data, [
                'tenant_id'   => $tenantId,
                'trial_days'  => $data['trial_days'] ?? 0,
                'is_active'   => $data['is_active'] ?? true,
            ]));
        });
    }
}
