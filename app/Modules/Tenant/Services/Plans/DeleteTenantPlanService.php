<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Plans;

use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\Repositories\TenantPlanRepositoryInterface;

final class DeleteTenantPlanService
{
    public function __construct(
        private readonly TenantPlanRepositoryInterface $plans,
        private readonly CurrentUserContextAccessorInterface $currentUser,
    ) {}

    public function execute(int|string $id, int $expectedVersion): Result
    {
        $existing = $this->plans->findById($id);
        if ($existing === null) { return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant plan not found.')); }
        $updated = $this->plans->updateWithVersion($id, $expectedVersion, ['is_active' => false, 'updated_by' => $this->currentUser->currentUserId()]);
        return $updated === null
            ? Result::failure(new Error(TenantErrorCode::VERSION_CONFLICT, 'Tenant plan changed since it was loaded. Refresh and try again.'))
            : Result::success($updated);
    }
}
