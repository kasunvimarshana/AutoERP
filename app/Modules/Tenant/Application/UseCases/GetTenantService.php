<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\UseCases;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Tenant\Application\Contracts\TenantRecordMapperInterface;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Domain\Constants\TenantErrorCode;
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
