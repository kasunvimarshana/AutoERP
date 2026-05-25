<?php

declare(strict_types=1);

namespace Modules\Finance\Application\UseCases\TaxGroups;

use Modules\Finance\Application\Contracts\UseCases\TaxGroups\DeleteTaxGroupServiceInterface;
use Modules\Finance\Application\Repositories\TaxGroupRepositoryInterface;
use Modules\Finance\Domain\Constants\FinanceErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class DeleteTaxGroupService implements DeleteTaxGroupServiceInterface
{
    public function __construct(private readonly TaxGroupRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            if (!$this->repository->delete($id)) {
                return Result::failure(new Error(FinanceErrorCode::NOT_FOUND, 'TaxGroup not found.'));
            }

            return Result::success(true);
        } catch (Throwable $exception) {
            return Result::failure(new Error(FinanceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
