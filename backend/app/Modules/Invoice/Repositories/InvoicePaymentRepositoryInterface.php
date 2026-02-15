<?php

declare(strict_types=1);

namespace App\Modules\Invoice\Repositories;

use App\Core\Repositories\BaseRepositoryInterface;

interface InvoicePaymentRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get payments by invoice.
     */
    public function getByInvoice(int $invoiceId);

    /**
     * Get total paid amount for invoice.
     */
    public function getTotalPaidForInvoice(int $invoiceId): float;
}
