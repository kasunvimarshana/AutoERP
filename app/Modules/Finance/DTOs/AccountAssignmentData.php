<?php

declare(strict_types=1);

namespace Modules\Finance\DTOs;

final readonly class AccountAssignmentData
{
    public function __construct(
        public int $tenantId,
        public int $accountRoleId,
        public int $accountId,
        public string $effectiveFrom,
        public ?int $organizationUnitId = null,
        public ?string $contextType = null,
        public ?int $contextId = null,
        public ?string $effectiveTo = null,
        public bool $isActive = true,
        public ?string $description = null,
        public ?int $actorId = null,
        public ?int $expectedVersion = null,
    ) {}
}
