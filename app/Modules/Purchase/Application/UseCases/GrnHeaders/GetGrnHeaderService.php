<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\UseCases\GrnHeaders;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Purchase\Application\Contracts\UseCases\GrnHeaders\GetGrnHeaderServiceInterface;
use Modules\Purchase\Application\Repositories\GrnHeaderRepositoryInterface;
use Modules\Purchase\Domain\Constants\PurchaseErrorCode;
use Throwable;

final class GetGrnHeaderService implements GetGrnHeaderServiceInterface
{
    public function __construct(private readonly GrnHeaderRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(PurchaseErrorCode::NOT_FOUND, 'GrnHeader not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(PurchaseErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}