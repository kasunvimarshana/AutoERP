<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\UseCases\Plans;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Tenant\Application\Repositories\TenantPlanRepositoryInterface;
use Modules\Tenant\Domain\Constants\TenantErrorCode;
use Throwable;

final class ListTenantPlansService
{
    public function __construct(private readonly TenantPlanRepositoryInterface $plans) {}

    public function execute(array $filters): Result
    {
        try {
            $result = $this->plans->pageByFilters(
                array_key_exists('is_active', $filters) ? (bool) $filters['is_active'] : null,
                isset($filters['billing_interval']) ? (string) $filters['billing_interval'] : null,
                isset($filters['search']) ? (string) $filters['search'] : null,
                max(1, (int) ($filters['per_page'] ?? 20)),
                max(1, (int) ($filters['page'] ?? 1)),
            );

            return Result::success($result);
        } catch (Throwable $exception) {
            return Result::failure(new Error(TenantErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
