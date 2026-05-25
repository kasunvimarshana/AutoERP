<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases\SalaryStructureLines;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\HR\Application\Contracts\UseCases\SalaryStructureLines\GetSalaryStructureLineServiceInterface;
use Modules\HR\Application\Repositories\SalaryStructureLineRepositoryInterface;
use Modules\HR\Domain\Constants\HrErrorCode;
use Throwable;

final class GetSalaryStructureLineService implements GetSalaryStructureLineServiceInterface
{
    public function __construct(private readonly SalaryStructureLineRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(HrErrorCode::NOT_FOUND, 'SalaryStructureLine not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(HrErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}