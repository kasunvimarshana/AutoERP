<?php

declare(strict_types=1);

namespace Modules\Invoice\Application\Contracts\UseCases\InvoiceLines;

use Modules\Core\Application\Results\Result;

interface GetInvoiceLineServiceInterface
{
    public function execute(int|string $id): Result;
}