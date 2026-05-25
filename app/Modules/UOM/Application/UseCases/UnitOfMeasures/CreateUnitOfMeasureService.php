<?php

declare(strict_types=1);

namespace Modules\UOM\Application\UseCases\UnitOfMeasures;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\UOM\Application\Contracts\UseCases\UnitOfMeasures\CreateUnitOfMeasureServiceInterface;
use Modules\UOM\Application\Repositories\UnitOfMeasureRepositoryInterface;
use Modules\UOM\Domain\Constants\UomErrorCode;
use Throwable;

final class CreateUnitOfMeasureService implements CreateUnitOfMeasureServiceInterface
{
    public function __construct(private readonly UnitOfMeasureRepositoryInterface $repository)
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
            return Result::failure(new Error(UomErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}