<?php

namespace Modules\SubscriptionBilling\Infrastructure\Repositories;

use Modules\SubscriptionBilling\Domain\Contracts\SubscriptionRepositoryInterface;
use Modules\SubscriptionBilling\Infrastructure\Models\SubscriptionModel;
use Modules\Shared\Infrastructure\Repositories\BaseEloquentRepository;

class SubscriptionRepository extends BaseEloquentRepository implements SubscriptionRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(new SubscriptionModel());
    }

    /**
     * Chunk through active (or trial) subscriptions whose current period has ended.
     * Uses orderBy('id') for stable, non-overlapping pagination.
     * Prevents execution timeout on large subscriber tables.
     */
    public function chunkDueForRenewal(string $tenantId = '', int $chunkSize = 100, callable $callback = null): void
    {
        $query = SubscriptionModel::whereIn('status', ['active', 'trial'])
            ->where('current_period_end', '<=', now());

        if ($tenantId !== '') {
            $query->where('tenant_id', $tenantId);
        }

        $query->orderBy('id')->chunk($chunkSize, $callback);
    }
}
