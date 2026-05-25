<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases\EmployeeContracts;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\HR\Application\Contracts\UseCases\EmployeeContracts\UpdateEmployeeContractServiceInterface;
use Modules\HR\Application\Repositories\EmployeeContractRepositoryInterface;
use Modules\HR\Domain\Constants\HrErrorCode;
use Throwable;

final class UpdateEmployeeContractService implements UpdateEmployeeContractServiceInterface
{
    public function __construct(private readonly EmployeeContractRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(HrErrorCode::NOT_FOUND, 'EmployeeContract not found.'));
            }

            return Result::success($this->repository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(HrErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}