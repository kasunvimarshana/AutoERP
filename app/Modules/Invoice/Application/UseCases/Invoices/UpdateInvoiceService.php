<?php

declare(strict_types=1);

namespace Modules\Invoice\Application\UseCases\Invoices;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Invoice\Application\Contracts\UseCases\Invoices\UpdateInvoiceServiceInterface;
use Modules\Invoice\Application\Repositories\InvoiceRepositoryInterface;
use Modules\Invoice\Domain\Constants\InvoiceErrorCode;
use Throwable;

final class UpdateInvoiceService implements UpdateInvoiceServiceInterface
{
    public function __construct(private readonly InvoiceRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(InvoiceErrorCode::NOT_FOUND, 'Invoice not found.'));
            }

            return Result::success($this->repository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(InvoiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}