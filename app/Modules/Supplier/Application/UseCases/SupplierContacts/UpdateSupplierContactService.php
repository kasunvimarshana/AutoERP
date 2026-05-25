<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\UseCases\SupplierContacts;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Supplier\Application\Contracts\UseCases\SupplierContacts\UpdateSupplierContactServiceInterface;
use Modules\Supplier\Application\Repositories\SupplierContactRepositoryInterface;
use Modules\Supplier\Domain\Constants\SupplierErrorCode;
use Throwable;

final class UpdateSupplierContactService implements UpdateSupplierContactServiceInterface
{
    public function __construct(private readonly SupplierContactRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(SupplierErrorCode::NOT_FOUND, 'SupplierContact not found.'));
            }

            return Result::success($this->repository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(SupplierErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}