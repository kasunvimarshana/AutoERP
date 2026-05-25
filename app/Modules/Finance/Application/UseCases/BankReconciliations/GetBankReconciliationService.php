<?php

declare(strict_types=1);

namespace Modules\Finance\Application\UseCases\BankReconciliations;

use Modules\Finance\Application\Contracts\UseCases\BankReconciliations\GetBankReconciliationServiceInterface;
use Modules\Finance\Application\Repositories\BankReconciliationRepositoryInterface;
use Modules\Finance\Domain\Constants\FinanceErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class GetBankReconciliationService implements GetBankReconciliationServiceInterface
{
    public function __construct(private readonly BankReconciliationRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);
            if ($record === null) {
                return Result::failure(new Error(FinanceErrorCode::NOT_FOUND, 'BankReconciliation not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(FinanceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
