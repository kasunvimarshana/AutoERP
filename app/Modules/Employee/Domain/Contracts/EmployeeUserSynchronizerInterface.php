<?php

declare(strict_types=1);

namespace Modules\Employee\Domain\Contracts;

interface EmployeeUserSynchronizerInterface
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
    public function synchronizeForEmployeeUpdate(
        int $tenantId,
        int $userId,
        ?int $orgUnitId,
        ?array $userPayload,
    ): void;
}
