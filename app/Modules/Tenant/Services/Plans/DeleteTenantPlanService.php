<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Plans;

use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\Repositories\TenantPlanRepositoryInterface;
use Throwable;

final class DeleteTenantPlanService
{
    public function __construct(private readonly TenantPlanRepositoryInterface $plans) {}

    public function execute(int|string $id): Result
    {
        try {
            if ($this->plans->findById($id) === null) {
                return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant plan not found.'));
            }

            return Result::success($this->plans->delete($id));
        } catch (Throwable $exception) {
            return Result::failure(new Error(TenantErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
