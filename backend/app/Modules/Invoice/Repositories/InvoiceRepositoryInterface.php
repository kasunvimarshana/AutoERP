<?php

declare(strict_types=1);

namespace App\Modules\Invoice\Repositories;

use App\Core\Repositories\BaseRepositoryInterface;

interface InvoiceRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find invoice by invoice number.
     */
    public function findByInvoiceNumber(string $invoiceNumber);

    /**
     * Get invoices by customer.
     */
    public function getByCustomer(int $customerId, int $perPage = 15);

    /**
     * Get invoices by status.
     */
    public function getByStatus(string $status, int $perPage = 15);

    /**
     * Get invoices by payment status.
     */
    public function getByPaymentStatus(string $paymentStatus, int $perPage = 15);

    /**
     * Get overdue invoices.
     */
    public function getOverdueInvoices(int $perPage = 15);

    /**
     * Get invoices by sales order.
     */
    public function getBySalesOrder(int $salesOrderId, int $perPage = 15);
}
