<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Plans;

use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\Repositories\TenantPlanRepositoryInterface;

final class GetTenantPlanService
{
    public function __construct(private readonly TenantPlanRepositoryInterface $plans) {}

    public function execute(int|string $id): Result
    {
        $record = $this->plans->findById($id);

        if ($record === null) {
            return Result::failure(new Error(
                TenantErrorCode::NOT_FOUND,
                'Tenant plan not found.',
            ));
        }

        return Result::success($record);
    }
}
