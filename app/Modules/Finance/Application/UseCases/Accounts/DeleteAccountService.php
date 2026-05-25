<?php

declare(strict_types=1);

namespace Modules\Finance\Application\UseCases\Accounts;

use Modules\Finance\Application\Contracts\UseCases\Accounts\DeleteAccountServiceInterface;
use Modules\Finance\Application\Repositories\AccountRepositoryInterface;
use Modules\Finance\Domain\Constants\FinanceErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class DeleteAccountService implements DeleteAccountServiceInterface
{
    public function __construct(private readonly AccountRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            if (!$this->repository->delete($id)) {
                return Result::failure(new Error(FinanceErrorCode::NOT_FOUND, 'Account not found.'));
            }

            return Result::success(true);
        } catch (Throwable $exception) {
            return Result::failure(new Error(FinanceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
