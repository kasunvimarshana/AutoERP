<?php

declare(strict_types=1);

namespace Modules\Finance\Application\UseCases\BankAccounts;

use Modules\Finance\Application\Contracts\UseCases\BankAccounts\DeleteBankAccountServiceInterface;
use Modules\Finance\Application\Repositories\BankAccountRepositoryInterface;
use Modules\Finance\Domain\Constants\FinanceErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class DeleteBankAccountService implements DeleteBankAccountServiceInterface
{
    public function __construct(private readonly BankAccountRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            if (!$this->repository->delete($id)) {
                return Result::failure(new Error(FinanceErrorCode::NOT_FOUND, 'BankAccount not found.'));
            }

            return Result::success(true);
        } catch (Throwable $exception) {
            return Result::failure(new Error(FinanceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
