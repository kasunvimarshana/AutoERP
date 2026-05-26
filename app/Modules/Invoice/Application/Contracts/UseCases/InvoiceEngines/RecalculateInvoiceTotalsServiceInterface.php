<?php

declare(strict_types=1);

namespace Modules\Invoice\Application\Contracts\UseCases\InvoiceEngines;

use Modules\Core\Application\Results\Result;

interface RecalculateInvoiceTotalsServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $invoiceId, array $payload): Result;
}
