<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\UseCases\Plans;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Tenant\Application\Contracts\UseCases\Plans\DeleteTenantPlanServiceInterface;
use Modules\Tenant\Application\Repositories\TenantPlanRepositoryInterface;
use Modules\Tenant\Domain\Constants\TenantErrorCode;
use Throwable;

final class DeleteTenantPlanService implements DeleteTenantPlanServiceInterface
{
    public function __construct(private readonly TenantPlanRepositoryInterface $plans)
    {
    }

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
