<?php

declare(strict_types=1);

namespace Modules\Invoice\Application\UseCases\InvoiceReferences;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Invoice\Application\Contracts\UseCases\InvoiceReferences\DeleteInvoiceReferenceServiceInterface;
use Modules\Invoice\Application\Repositories\InvoiceReferenceRepositoryInterface;
use Modules\Invoice\Domain\Constants\InvoiceErrorCode;
use Throwable;

final class DeleteInvoiceReferenceService implements DeleteInvoiceReferenceServiceInterface
{
    public function __construct(private readonly InvoiceReferenceRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(InvoiceErrorCode::NOT_FOUND, 'InvoiceReference not found.'));
            }

            $this->repository->delete($id);

            return Result::success(null);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InvoiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}