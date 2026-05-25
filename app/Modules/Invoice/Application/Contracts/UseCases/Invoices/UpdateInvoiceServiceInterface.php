<?php

declare(strict_types=1);

namespace Modules\Invoice\Application\Contracts\UseCases\Invoices;

use Modules\Core\Application\Results\Result;

interface UpdateInvoiceServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}