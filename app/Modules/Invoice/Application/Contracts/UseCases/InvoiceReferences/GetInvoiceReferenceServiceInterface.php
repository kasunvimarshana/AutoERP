<?php

declare(strict_types=1);

namespace Modules\Invoice\Application\Contracts\UseCases\InvoiceReferences;

use Modules\Core\Application\Results\Result;

interface GetInvoiceReferenceServiceInterface
{
    public function execute(int|string $id): Result;
}