<?php

declare(strict_types=1);

namespace Modules\Customer\Domain\Contracts;

interface CustomerUserSynchronizerInterface
{
    /**
     * @param  array<string, mixed>|null  $userPayload
     */
    public function resolveUserIdForCreate(
        int $tenantId,
        ?int $orgUnitId,
        ?int $requestedUserId,
        ?array $userPayload,
    ): int;

    /**
     * @param  array<string, mixed>|null  $userPayload
     */
    public function synchronizeForCustomerUpdate(
        int $tenantId,
        int $userId,
        ?int $orgUnitId,
        ?array $userPayload,
    ): void;
}
