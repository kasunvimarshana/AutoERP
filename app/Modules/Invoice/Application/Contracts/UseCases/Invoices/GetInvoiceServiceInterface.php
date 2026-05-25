<?php

declare(strict_types=1);

namespace Modules\Invoice\Application\Contracts\UseCases\Invoices;

use Modules\Core\Application\Results\Result;

interface GetInvoiceServiceInterface
{
    public function execute(int|string $id): Result;
}