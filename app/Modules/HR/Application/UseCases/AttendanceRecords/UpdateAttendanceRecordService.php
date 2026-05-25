<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases\AttendanceRecords;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\HR\Application\Contracts\UseCases\AttendanceRecords\UpdateAttendanceRecordServiceInterface;
use Modules\HR\Application\Repositories\AttendanceRecordRepositoryInterface;
use Modules\HR\Domain\Constants\HrErrorCode;
use Throwable;

final class UpdateAttendanceRecordService implements UpdateAttendanceRecordServiceInterface
{
    public function __construct(private readonly AttendanceRecordRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(HrErrorCode::NOT_FOUND, 'AttendanceRecord not found.'));
            }

            return Result::success($this->repository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(HrErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}