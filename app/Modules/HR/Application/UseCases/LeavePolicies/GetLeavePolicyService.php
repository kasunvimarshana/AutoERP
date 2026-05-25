<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases\LeavePolicies;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\HR\Application\Contracts\UseCases\LeavePolicies\GetLeavePolicyServiceInterface;
use Modules\HR\Application\Repositories\LeavePolicyRepositoryInterface;
use Modules\HR\Domain\Constants\HrErrorCode;
use Throwable;

final class GetLeavePolicyService implements GetLeavePolicyServiceInterface
{
    public function __construct(private readonly LeavePolicyRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(HrErrorCode::NOT_FOUND, 'LeavePolicy not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(HrErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}