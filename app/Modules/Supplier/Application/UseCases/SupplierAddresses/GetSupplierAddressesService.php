<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\UseCases\SupplierAddresses;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Supplier\Application\Contracts\UseCases\SupplierAddresses\GetSupplierAddressesServiceInterface;
use Modules\Supplier\Application\Repositories\SupplierAddressesRepositoryInterface;
use Modules\Supplier\Domain\Constants\SupplierErrorCode;
use Throwable;

final class GetSupplierAddressesService implements GetSupplierAddressesServiceInterface
{
    public function __construct(private readonly SupplierAddressesRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(SupplierErrorCode::NOT_FOUND, 'SupplierAddresses not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(SupplierErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}