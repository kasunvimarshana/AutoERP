<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\UseCases\SupplierContacts;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Supplier\Application\Contracts\UseCases\SupplierContacts\GetSupplierContactServiceInterface;
use Modules\Supplier\Application\Repositories\SupplierContactRepositoryInterface;
use Modules\Supplier\Domain\Constants\SupplierErrorCode;
use Throwable;

final class GetSupplierContactService implements GetSupplierContactServiceInterface
{
    public function __construct(private readonly SupplierContactRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(SupplierErrorCode::NOT_FOUND, 'SupplierContact not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(SupplierErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}