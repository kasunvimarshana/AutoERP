<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases\Payslips;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\HR\Application\Contracts\UseCases\Payslips\GetPayslipServiceInterface;
use Modules\HR\Application\Repositories\PayslipRepositoryInterface;
use Modules\HR\Domain\Constants\HrErrorCode;
use Throwable;

final class GetPayslipService implements GetPayslipServiceInterface
{
    public function __construct(private readonly PayslipRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(HrErrorCode::NOT_FOUND, 'Payslip not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(HrErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}