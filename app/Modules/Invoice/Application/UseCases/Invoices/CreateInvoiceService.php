<?php

declare(strict_types=1);

namespace Modules\Invoice\Application\UseCases\Invoices;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Invoice\Application\Contracts\UseCases\Invoices\CreateInvoiceServiceInterface;
use Modules\Invoice\Application\Repositories\InvoiceRepositoryInterface;
use Modules\Invoice\Domain\Constants\InvoiceErrorCode;
use Throwable;

final class CreateInvoiceService implements CreateInvoiceServiceInterface
{
    public function __construct(private readonly InvoiceRepositoryInterface $repository)
    {
    }

    public function execute(array $payload): Result
    {
        try {
            if (! array_key_exists('row_version', $payload)) {
                $payload['row_version'] = 1;
            }

            return Result::success($this->repository->create($payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(InvoiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}