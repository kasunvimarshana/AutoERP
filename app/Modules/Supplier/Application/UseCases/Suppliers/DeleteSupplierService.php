<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\UseCases\Suppliers;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Supplier\Application\Contracts\UseCases\Suppliers\DeleteSupplierServiceInterface;
use Modules\Supplier\Application\Repositories\SupplierRepositoryInterface;
use Modules\Supplier\Domain\Constants\SupplierErrorCode;
use Throwable;

final class DeleteSupplierService implements DeleteSupplierServiceInterface
{
    public function __construct(private readonly SupplierRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(SupplierErrorCode::NOT_FOUND, 'Supplier not found.'));
            }

            $this->repository->delete($id);

            return Result::success(null);
        } catch (Throwable $exception) {
            return Result::failure(new Error(SupplierErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}