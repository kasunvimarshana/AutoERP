<?php

declare(strict_types=1);

namespace Modules\Finance\Application\UseCases\TaxRules;

use Modules\Finance\Application\Contracts\UseCases\TaxRules\DeleteTaxRuleServiceInterface;
use Modules\Finance\Application\Repositories\TaxRuleRepositoryInterface;
use Modules\Finance\Domain\Constants\FinanceErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class DeleteTaxRuleService implements DeleteTaxRuleServiceInterface
{
    public function __construct(private readonly TaxRuleRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            if (!$this->repository->delete($id)) {
                return Result::failure(new Error(FinanceErrorCode::NOT_FOUND, 'TaxRule not found.'));
            }

            return Result::success(true);
        } catch (Throwable $exception) {
            return Result::failure(new Error(FinanceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
