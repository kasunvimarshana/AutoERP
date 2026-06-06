<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Tenant\Constants\TenantErrorCode;
use Modules\Tenant\Contracts\TenantRecordMapperInterface;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Throwable;

final class GetTenantService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantRecordMapperInterface $mapper,
    ) {}

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->tenants->findById($id);

            if ($record === null) {
                return Result::failure(new Error(TenantErrorCode::NOT_FOUND, 'Tenant not found.'));
            }

            return Result::success($this->mapper->toValueData($record));
        } catch (Throwable $exception) {
            return Result::failure(new Error(TenantErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
