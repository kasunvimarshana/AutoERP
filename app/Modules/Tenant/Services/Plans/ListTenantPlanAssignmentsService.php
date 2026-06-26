<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Plans;

use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\Data\TenantDirectoryFilters;
use Modules\Tenant\Repositories\TenantPlanRepositoryInterface;
use Modules\Tenant\Repositories\TenantRepositoryInterface;

final class ListTenantPlanAssignmentsService
{
    public function __construct(
        private readonly TenantPlanRepositoryInterface $plans,
        private readonly TenantRepositoryInterface $tenants,
    ) {}

    /** @param array<string, mixed> $filters */
    public function execute(int $planId, array $filters): Result
    {
        if ($this->plans->findById($planId) === null) {
            return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant plan not found.'));
        }

        return Result::success($this->tenants->pageByFilters(new TenantDirectoryFilters(
            status: $this->nullableString($filters['status'] ?? null),
            search: $this->nullableString($filters['search'] ?? null),
            onboardingStatus: null,
            domainOperationalStatus: null,
            subscriptionState: null,
            subscriptionEffectiveStatus: $this->nullableString($filters['subscription_effective_status'] ?? null),
            planId: $planId,
            expiresWithinDays: null,
            perPage: min(max((int) ($filters['per_page'] ?? 20), 1), 100),
            page: max((int) ($filters['page'] ?? 1), 1),
        )));
    }

    private function nullableString(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === '' ? null : $value;
    }
}
