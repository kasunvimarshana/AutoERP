<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases\Departments;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\HR\Application\Contracts\UseCases\Departments\CreateDepartmentServiceInterface;
use Modules\HR\Application\Repositories\DepartmentRepositoryInterface;
use Modules\HR\Domain\Constants\HrErrorCode;
use Throwable;

final class CreateDepartmentService implements CreateDepartmentServiceInterface
{
    public function __construct(private readonly DepartmentRepositoryInterface $repository)
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
            return Result::failure(new Error(HrErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}