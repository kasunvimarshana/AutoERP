<?php

declare(strict_types=1);

namespace Modules\Invoice\Application\UseCases\InvoiceLines;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Invoice\Application\Contracts\UseCases\InvoiceLines\UpdateInvoiceLineServiceInterface;
use Modules\Invoice\Application\Repositories\InvoiceLineRepositoryInterface;
use Modules\Invoice\Domain\Constants\InvoiceErrorCode;
use Throwable;

final class UpdateInvoiceLineService implements UpdateInvoiceLineServiceInterface
{
    public function __construct(private readonly InvoiceLineRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(InvoiceErrorCode::NOT_FOUND, 'InvoiceLine not found.'));
            }

            return Result::success($this->repository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(InvoiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}