<?php

declare(strict_types=1);

namespace Modules\Finance\Application\UseCases\BankReconciliations;

use Modules\Finance\Application\Contracts\UseCases\BankReconciliations\ListBankReconciliationsServiceInterface;
use Modules\Finance\Application\Repositories\BankReconciliationRepositoryInterface;
use Modules\Finance\Domain\Constants\FinanceDefaults;
use Modules\Finance\Domain\Constants\FinanceErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class ListBankReconciliationsService implements ListBankReconciliationsServiceInterface
{
    public function __construct(private readonly BankReconciliationRepositoryInterface $repository)
    {
    }

    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result
    {
        try {
            $resolvedPage = $page > 0 ? $page : FinanceDefaults::DEFAULT_PAGE;
            $resolvedPerPage = $perPage > 0
                ? min($perPage, (int) config('finance.pagination.max_per_page', FinanceDefaults::MAX_PER_PAGE))
                : (int) config('finance.pagination.default_per_page', FinanceDefaults::DEFAULT_PER_PAGE);

            unset($criteria['search']);

            return Result::success($this->repository->page($criteria, $resolvedPerPage, $resolvedPage));
        } catch (Throwable $exception) {
            return Result::failure(new Error(FinanceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
