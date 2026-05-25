<?php

declare(strict_types=1);

namespace Modules\Finance\Application\UseCases\ArTransactions;

use Modules\Finance\Application\Contracts\UseCases\ArTransactions\UpdateArTransactionServiceInterface;
use Modules\Finance\Application\Repositories\ArTransactionRepositoryInterface;
use Modules\Finance\Domain\Constants\FinanceErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class UpdateArTransactionService implements UpdateArTransactionServiceInterface
{
    public function __construct(private readonly ArTransactionRepositoryInterface $repository)
    {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result
    {
        try {
            return Result::success($this->repository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(FinanceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
