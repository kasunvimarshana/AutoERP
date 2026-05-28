<?php

declare(strict_types=1);

namespace Modules\Item\Application\UseCases\Items;

use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Item\Application\Contracts\UseCases\Items\DeleteItemServiceInterface;
use Modules\Item\Application\Repositories\ItemRepositoryInterface;
use Modules\Item\Domain\Constants\ItemErrorCode;
use Throwable;

final class DeleteItemService implements DeleteItemServiceInterface
{
    public function __construct(
        private readonly ItemRepositoryInterface $repository,
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

            if ($this->repository->findByIdInTenant($id, $tenantId) === null) {
                return Result::failure(new Error(ItemErrorCode::NOT_FOUND, 'Item not found.'));
            }

            $this->repository->delete($id);

            return Result::success(null);
        } catch (Throwable $exception) {
            return Result::failure(new Error(ItemErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
