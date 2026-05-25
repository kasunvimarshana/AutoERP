<?php

declare(strict_types=1);

namespace Modules\Invoice\Application\UseCases\Invoices;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Invoice\Application\Contracts\UseCases\Invoices\GetInvoiceServiceInterface;
use Modules\Invoice\Application\Repositories\InvoiceRepositoryInterface;
use Modules\Invoice\Domain\Constants\InvoiceErrorCode;
use Throwable;

final class GetInvoiceService implements GetInvoiceServiceInterface
{
    public function __construct(private readonly InvoiceRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(InvoiceErrorCode::NOT_FOUND, 'Invoice not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InvoiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}