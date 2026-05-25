<?php

declare(strict_types=1);

namespace Modules\UOM\Application\UseCases\UnitOfMeasures;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\UOM\Application\Contracts\UseCases\UnitOfMeasures\DeleteUnitOfMeasureServiceInterface;
use Modules\UOM\Application\Repositories\UnitOfMeasureRepositoryInterface;
use Modules\UOM\Domain\Constants\UomErrorCode;
use Throwable;

final class DeleteUnitOfMeasureService implements DeleteUnitOfMeasureServiceInterface
{
    public function __construct(private readonly UnitOfMeasureRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(UomErrorCode::NOT_FOUND, 'UnitOfMeasure not found.'));
            }

            $this->repository->delete($id);

            return Result::success(null);
        } catch (Throwable $exception) {
            return Result::failure(new Error(UomErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}