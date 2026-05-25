<?php

declare(strict_types=1);

namespace Modules\Invoice\Application\Contracts\UseCases\InvoiceLines;

use Modules\Core\Application\Results\Result;

interface DeleteInvoiceLineServiceInterface
{
    public function execute(int|string $id): Result;
}