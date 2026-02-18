<?php

declare(strict_types=1);

namespace Modules\Accounting\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Accounting\Entities\Invoice;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;

/**
 * Invoice Repository Interface
 *
 * Defines the contract for invoice data access operations.
 */
interface InvoiceRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find invoice by invoice number.
     */
    public function findByInvoiceNumber(string $invoiceNumber): ?Invoice;

    /**
     * Get invoices by customer.
     */
    public function getByCustomer(int $customerId): Collection;

    /**
     * Get invoices by status.
     */
    public function getByStatus(string $status): Collection;

    /**
     * Get overdue invoices.
     */
    public function getOverdueInvoices(): Collection;

    /**
     * Get unpaid invoices.
     */
    public function getUnpaidInvoices(): Collection;

    /**
     * Get invoices by date range.
     */
    public function getByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): Collection;

    /**
     * Get aging report data.
     */
    public function getAgingReport(): Collection;
}
