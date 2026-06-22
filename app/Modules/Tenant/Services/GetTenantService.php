<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\Repositories\TenantRepositoryInterface;

final class GetTenantService
{
    public function __construct(private readonly TenantRepositoryInterface $tenants) {}
    public function execute(int|string $id): Result
    {
        $tenant = $this->tenants->findById($id);
        return $tenant === null
            ? Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant not found.'))
            : Result::success($tenant);
    }
}
