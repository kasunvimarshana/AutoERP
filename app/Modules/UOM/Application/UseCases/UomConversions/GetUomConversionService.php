<?php

declare(strict_types=1);

namespace Modules\UOM\Application\UseCases\UomConversions;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\UOM\Application\Contracts\UseCases\UomConversions\GetUomConversionServiceInterface;
use Modules\UOM\Application\Repositories\UomConversionRepositoryInterface;
use Modules\UOM\Domain\Constants\UomErrorCode;
use Throwable;

final class GetUomConversionService implements GetUomConversionServiceInterface
{
    public function __construct(private readonly UomConversionRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(UomErrorCode::NOT_FOUND, 'UomConversion not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(UomErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}