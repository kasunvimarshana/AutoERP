<?php

declare(strict_types=1);

namespace Modules\Item\Application\UseCases\ComboItems;

use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Item\Application\Contracts\UseCases\ComboItems\GetComboItemServiceInterface;
use Modules\Item\Application\Repositories\ComboItemRepositoryInterface;
use Modules\Item\Domain\Constants\ItemErrorCode;
use Throwable;

final class GetComboItemService implements GetComboItemServiceInterface
{
    public function __construct(
        private readonly ComboItemRepositoryInterface $repository,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
    ) {
    }

    public function execute(int|string $id): Result
    {
        try {
            $tenantId = $this->currentTenant->currentTenantId();
            if ($tenantId === null) {
                return Result::failure(new Error(ItemErrorCode::INVALID_VALUE, 'Tenant context is required.'));
            }

            $record = $this->repository->findByIdInTenant($id, $tenantId);

            if ($record === null) {
                return Result::failure(new Error(ItemErrorCode::NOT_FOUND, 'ComboItem not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(ItemErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
