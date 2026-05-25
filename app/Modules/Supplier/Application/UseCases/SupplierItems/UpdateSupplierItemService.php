<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\UseCases\SupplierItems;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Supplier\Application\Contracts\UseCases\SupplierItems\UpdateSupplierItemServiceInterface;
use Modules\Supplier\Application\Repositories\SupplierItemRepositoryInterface;
use Modules\Supplier\Domain\Constants\SupplierErrorCode;
use Throwable;

final class UpdateSupplierItemService implements UpdateSupplierItemServiceInterface
{
    public function __construct(private readonly SupplierItemRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(SupplierErrorCode::NOT_FOUND, 'SupplierItem not found.'));
            }

            return Result::success($this->repository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(SupplierErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}