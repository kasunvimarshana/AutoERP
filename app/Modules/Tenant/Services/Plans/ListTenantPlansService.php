<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Plans;

use Modules\Core\Results\Result;
use Modules\Tenant\Repositories\TenantPlanRepositoryInterface;

final class ListTenantPlansService
{
    private const DEFAULT_PAGE_SIZE = 20;
    private const MAX_PAGE_SIZE = 100;

    public function __construct(private readonly TenantPlanRepositoryInterface $plans) {}

    /** @param array<string, mixed> $filters */
    public function execute(array $filters): Result
    {
        return Result::success($this->plans->pageByFilters(
            isActive: array_key_exists('is_active', $filters)
                ? (bool) $filters['is_active']
                : null,
            billingInterval: isset($filters['billing_interval'])
                ? (string) $filters['billing_interval']
                : null,
            search: isset($filters['search']) ? (string) $filters['search'] : null,
            perPage: min(
                max((int) ($filters['per_page'] ?? self::DEFAULT_PAGE_SIZE), 1),
                self::MAX_PAGE_SIZE,
            ),
            page: max((int) ($filters['page'] ?? 1), 1),
        ));
    }
}
