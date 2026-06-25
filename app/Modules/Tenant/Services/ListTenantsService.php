<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use Modules\Core\Results\Result;
use Modules\Tenant\Data\TenantDirectoryFilters;
use Modules\Tenant\Repositories\TenantRepositoryInterface;

final class ListTenantsService
{
    public function __construct(private readonly TenantRepositoryInterface $tenants) {}

    /** @param array<string, mixed> $filters */
    public function execute(array $filters): Result
    {
        return Result::success($this->tenants->pageByFilters(new TenantDirectoryFilters(
            status: $this->nullableString($filters['status'] ?? null),
            search: $this->nullableString($filters['search'] ?? null),
            onboardingStatus: $this->nullableString($filters['onboarding_status'] ?? null),
            domainOperationalStatus: $this->nullableString($filters['domain_operational_status'] ?? null),
            subscriptionState: $this->nullableString($filters['subscription_state'] ?? null),
            planId: $this->positiveInt($filters['plan_id'] ?? null),
            expiresWithinDays: $this->positiveInt($filters['expires_within_days'] ?? null),
            perPage: min(max((int) ($filters['per_page'] ?? 20), 1), 100),
            page: max((int) ($filters['page'] ?? 1), 1),
        )));
    }

    private function nullableString(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === '' ? null : $value;
    }

    private function positiveInt(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }
}
