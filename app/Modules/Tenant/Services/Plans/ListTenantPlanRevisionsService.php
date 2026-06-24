<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Plans;

use Modules\Core\Contracts\ErrorNormalizerInterface;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\Repositories\TenantPlanRepositoryInterface;
use Modules\Tenant\Repositories\TenantPlanRevisionRepositoryInterface;
use Throwable;

final class ListTenantPlanRevisionsService
{
    public function __construct(
        private readonly TenantPlanRepositoryInterface $plans,
        private readonly TenantPlanRevisionRepositoryInterface $revisions,
        private readonly ErrorNormalizerInterface $errors,
    ) {}

    public function execute(int|string $planId): Result
    {
        try {
            if ($this->plans->findById($planId) === null) {
                return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant plan not found.'));
            }

            return Result::success($this->revisions->listByPlan($planId));
        } catch (Throwable $exception) {
            return Result::failure($this->errors->normalize(
                $exception,
                TenantErrorCode::INVALID_VALUE,
                ['operation' => 'tenant.plan.revisions.list', 'plan_id' => (string) $planId],
            ));
        }
    }
}
