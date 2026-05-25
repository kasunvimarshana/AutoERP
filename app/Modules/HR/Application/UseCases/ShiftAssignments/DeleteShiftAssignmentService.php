<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases\ShiftAssignments;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\HR\Application\Contracts\UseCases\ShiftAssignments\DeleteShiftAssignmentServiceInterface;
use Modules\HR\Application\Repositories\ShiftAssignmentRepositoryInterface;
use Modules\HR\Domain\Constants\HrErrorCode;
use Throwable;

final class DeleteShiftAssignmentService implements DeleteShiftAssignmentServiceInterface
{
    public function __construct(private readonly ShiftAssignmentRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(HrErrorCode::NOT_FOUND, 'ShiftAssignment not found.'));
            }

            $this->repository->delete($id);

            return Result::success(null);
        } catch (Throwable $exception) {
            return Result::failure(new Error(HrErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}