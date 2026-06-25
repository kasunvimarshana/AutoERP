<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Subscriptions;

use Modules\Core\DTOs\DataRecord;
use Modules\Tenant\Exceptions\TenantSubscriptionDataException;

final class TenantSubscriptionPresenter
{
    public function __construct(private readonly TenantSubscriptionPolicy $policy) {}

    /** @param array<string, mixed>|DataRecord $subscription
     *  @return array<string, mixed>
     */
    public function present(array|DataRecord $subscription): array
    {
        $values = $subscription instanceof DataRecord
            ? $subscription->toArray()
            : $subscription;

        try {
            $values['effective_status'] = $this->policy->statusAt($values);
        } catch (TenantSubscriptionDataException) {
            $values['effective_status'] = TenantSubscriptionPolicy::INVALID;
        }

        return $values;
    }
}
