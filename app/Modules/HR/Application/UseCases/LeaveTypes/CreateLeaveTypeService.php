<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases\LeaveTypes;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\HR\Application\Contracts\UseCases\LeaveTypes\CreateLeaveTypeServiceInterface;
use Modules\HR\Application\Repositories\LeaveTypeRepositoryInterface;
use Modules\HR\Domain\Constants\HrErrorCode;
use Throwable;

final class CreateLeaveTypeService implements CreateLeaveTypeServiceInterface
{
    public function __construct(private readonly LeaveTypeRepositoryInterface $repository)
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