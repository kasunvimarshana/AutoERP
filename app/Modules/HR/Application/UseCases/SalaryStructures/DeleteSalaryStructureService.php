<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases\SalaryStructures;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\HR\Application\Contracts\UseCases\SalaryStructures\DeleteSalaryStructureServiceInterface;
use Modules\HR\Application\Repositories\SalaryStructureRepositoryInterface;
use Modules\HR\Domain\Constants\HrErrorCode;
use Throwable;

final class DeleteSalaryStructureService implements DeleteSalaryStructureServiceInterface
{
    public function __construct(private readonly SalaryStructureRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(HrErrorCode::NOT_FOUND, 'SalaryStructure not found.'));
            }

            $this->repository->delete($id);

            return Result::success(null);
        } catch (Throwable $exception) {
            return Result::failure(new Error(HrErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}