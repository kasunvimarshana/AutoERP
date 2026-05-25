<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases\Departments;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\HR\Application\Contracts\UseCases\Departments\DeleteDepartmentServiceInterface;
use Modules\HR\Application\Repositories\DepartmentRepositoryInterface;
use Modules\HR\Domain\Constants\HrErrorCode;
use Throwable;

final class DeleteDepartmentService implements DeleteDepartmentServiceInterface
{
    public function __construct(private readonly DepartmentRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(HrErrorCode::NOT_FOUND, 'Department not found.'));
            }

            $this->repository->delete($id);

            return Result::success(null);
        } catch (Throwable $exception) {
            return Result::failure(new Error(HrErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}