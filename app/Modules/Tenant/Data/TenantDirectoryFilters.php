<?php

declare(strict_types=1);

namespace Modules\Tenant\Data;

final readonly class TenantDirectoryFilters
{
    public function __construct(
        public ?string $status,
        public ?string $search,
        public ?string $onboardingStatus,
        public ?string $domainOperationalStatus,
        public ?string $subscriptionState,
        public ?string $subscriptionEffectiveStatus,
        public ?int $planId,
        public ?int $expiresWithinDays,
        public int $perPage,
        public int $page,
    ) {}
}
