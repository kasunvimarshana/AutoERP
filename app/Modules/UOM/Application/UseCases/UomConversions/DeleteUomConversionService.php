<?php

declare(strict_types=1);

namespace Modules\UOM\Application\UseCases\UomConversions;

use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\UOM\Application\Repositories\UomConversionRepositoryInterface;
use Modules\UOM\Domain\Constants\UomErrorCode;
use Throwable;

final class DeleteUomConversionService
{
    public function __construct(
        private readonly UomConversionRepositoryInterface $repository,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
    ) {}

    public function execute(int|string $id): Result
    {
        try {
            $tenantId = $this->currentTenant->currentTenantId();
            if ($tenantId === null) {
                return Result::failure(new Error(UomErrorCode::INVALID_VALUE, 'Tenant context is required.'));
            }

            if ($this->repository->findByIdInTenant($id, $tenantId) === null) {
                return Result::failure(new Error(UomErrorCode::NOT_FOUND, 'UomConversion not found.'));
            }

            $this->repository->delete($id);

            return Result::success(null);
        } catch (Throwable $exception) {
            return Result::failure(new Error(UomErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
