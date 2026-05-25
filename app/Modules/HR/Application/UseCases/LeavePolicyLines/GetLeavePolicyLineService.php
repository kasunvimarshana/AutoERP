<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases\LeavePolicyLines;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\HR\Application\Contracts\UseCases\LeavePolicyLines\GetLeavePolicyLineServiceInterface;
use Modules\HR\Application\Repositories\LeavePolicyLineRepositoryInterface;
use Modules\HR\Domain\Constants\HrErrorCode;
use Throwable;

final class GetLeavePolicyLineService implements GetLeavePolicyLineServiceInterface
{
    public function __construct(private readonly LeavePolicyLineRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(HrErrorCode::NOT_FOUND, 'LeavePolicyLine not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(HrErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}