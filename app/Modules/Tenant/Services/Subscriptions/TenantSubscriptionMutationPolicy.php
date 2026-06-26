<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Subscriptions;

use InvalidArgumentException;
use Modules\Core\DTOs\DataRecord;
use Modules\Tenant\Constants\TenantCurrentSubscriptionState;
use Modules\Tenant\Constants\TenantStatus;
use Modules\Tenant\Constants\TenantSubscriptionOperation;

final class TenantSubscriptionMutationPolicy
{
    public function assertTenantCanMutate(DataRecord $tenant): void
    {
        if ((string) $tenant->get('status') === TenantStatus::ARCHIVED) {
            throw new InvalidArgumentException(
                'Archived tenants are read-only and cannot receive subscription changes.',
            );
        }
    }

    public function operationAllowed(
        string $operation,
        ?DataRecord $current,
        ?int $expectedPointerVersion,
    ): bool {
        if ($operation === TenantSubscriptionOperation::ASSIGN) {
            return $current === null
                ? $expectedPointerVersion === null
                : $expectedPointerVersion === (int) $current->get('row_version', 0)
                    && in_array($current->get('current_state'), [
                        TenantCurrentSubscriptionState::CANCELLED,
                        TenantCurrentSubscriptionState::EXPIRED,
                    ], true);
        }

        return $current !== null
            && $expectedPointerVersion !== null
            && $expectedPointerVersion === (int) $current->get('row_version', 0)
            && $current->get('current_state') === TenantCurrentSubscriptionState::ASSIGNED;
    }
}
