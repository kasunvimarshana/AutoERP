<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\UseCases\SupplierItems;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Supplier\Application\Contracts\UseCases\SupplierItems\DeleteSupplierItemServiceInterface;
use Modules\Supplier\Application\Repositories\SupplierItemRepositoryInterface;
use Modules\Supplier\Domain\Constants\SupplierErrorCode;
use Throwable;

final class DeleteSupplierItemService implements DeleteSupplierItemServiceInterface
{
    public function __construct(private readonly SupplierItemRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(SupplierErrorCode::NOT_FOUND, 'SupplierItem not found.'));
            }

            $this->repository->delete($id);

            return Result::success(null);
        } catch (Throwable $exception) {
            return Result::failure(new Error(SupplierErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}