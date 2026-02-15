<?php

declare(strict_types=1);

namespace App\Modules\Invoice\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Invoice\Models\Invoice;

class InvoiceRepository extends BaseRepository implements InvoiceRepositoryInterface
{
    /**
     * InvoiceRepository constructor.
     */
    public function __construct(Invoice $model)
    {
        parent::__construct($model);
    }

    /**
     * Find invoice by invoice number.
     */
    public function findByInvoiceNumber(string $invoiceNumber)
    {
        return $this->model->where('invoice_number', $invoiceNumber)->first();
    }

    /**
     * Get invoices by customer.
     */
    public function getByCustomer(int $customerId, int $perPage = 15)
    {
        return $this->model
            ->where('customer_id', $customerId)
            ->with(['items', 'customer', 'currency', 'payments'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get invoices by status.
     */
    public function getByStatus(string $status, int $perPage = 15)
    {
        return $this->model
            ->where('status', $status)
            ->with(['items', 'customer', 'currency', 'payments'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get invoices by payment status.
     */
    public function getByPaymentStatus(string $paymentStatus, int $perPage = 15)
    {
        return $this->model
            ->where('payment_status', $paymentStatus)
            ->with(['items', 'customer', 'currency', 'payments'])
            ->orderBy('due_date', 'asc')
            ->paginate($perPage);
    }

    /**
     * Get overdue invoices.
     */
    public function getOverdueInvoices(int $perPage = 15)
    {
        return $this->model
            ->where('due_date', '<', now())
            ->where('payment_status', '!=', 'paid')
            ->with(['items', 'customer', 'currency', 'payments'])
            ->orderBy('due_date', 'asc')
            ->paginate($perPage);
    }

    /**
     * Get invoices by sales order.
     */
    public function getBySalesOrder(int $salesOrderId, int $perPage = 15)
    {
        return $this->model
            ->where('sales_order_id', $salesOrderId)
            ->with(['items', 'customer', 'currency', 'payments'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
