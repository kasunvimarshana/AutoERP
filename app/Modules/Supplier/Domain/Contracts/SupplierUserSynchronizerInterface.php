<?php

declare(strict_types=1);

namespace Modules\Supplier\Domain\Contracts;

interface SupplierUserSynchronizerInterface
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
    public function synchronizeForSupplierUpdate(
        int $tenantId,
        int $userId,
        ?int $orgUnitId,
        ?array $userPayload,
    ): void;
}
