<?php

declare(strict_types=1);

namespace Modules\Supplier\Application\UseCases\SupplierItems;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Supplier\Application\Contracts\UseCases\SupplierItems\CreateSupplierItemServiceInterface;
use Modules\Supplier\Application\Repositories\SupplierItemRepositoryInterface;
use Modules\Supplier\Domain\Constants\SupplierErrorCode;
use Throwable;

final class CreateSupplierItemService implements CreateSupplierItemServiceInterface
{
    public function __construct(private readonly SupplierItemRepositoryInterface $repository)
    {
    }

    public function execute(array $payload): Result
    {
        try {
            if (! array_key_exists('row_version', $payload)) {
                $payload['row_version'] = 1;
            }

            return Result::success($this->repository->create($payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(SupplierErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}