<?php

namespace App\Modules\InvoicingManagement\Repositories;

use App\Core\Base\BaseRepository;
use App\Modules\InvoicingManagement\Models\Invoice;

class InvoiceRepository extends BaseRepository
{
    public function __construct(Invoice $model)
    {
        parent::__construct($model);
    }

    /**
     * Search invoices by various criteria
     */
    public function search(array $criteria)
    {
        $query = $this->model->query();

        if (!empty($criteria['search'])) {
            $search = $criteria['search'];
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        if (!empty($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        if (!empty($criteria['customer_id'])) {
            $query->where('customer_id', $criteria['customer_id']);
        }

        if (!empty($criteria['job_card_id'])) {
            $query->where('job_card_id', $criteria['job_card_id']);
        }

        if (!empty($criteria['date_from'])) {
            $query->where('invoice_date', '>=', $criteria['date_from']);
        }

        if (!empty($criteria['date_to'])) {
            $query->where('invoice_date', '<=', $criteria['date_to']);
        }

        if (!empty($criteria['tenant_id'])) {
            $query->where('tenant_id', $criteria['tenant_id']);
        }

        return $query->with(['customer', 'jobCard', 'items', 'payments'])
            ->orderBy('invoice_date', 'desc')
            ->paginate($criteria['per_page'] ?? 15);
    }

    /**
     * Find invoice by invoice number
     */
    public function findByInvoiceNumber(string $invoiceNumber): ?Invoice
    {
        return $this->model->where('invoice_number', $invoiceNumber)->first();
    }

    /**
     * Get invoices by status
     */
    public function getByStatus(string $status)
    {
        return $this->model->byStatus($status)->with(['customer', 'items'])->get();
    }

    /**
     * Get overdue invoices
     */
    public function getOverdue()
    {
        return $this->model->overdue()->with(['customer', 'items'])->get();
    }

    /**
     * Get paid invoices
     */
    public function getPaid()
    {
        return $this->model->paid()->with(['customer', 'items'])->get();
    }

    /**
     * Get unpaid invoices
     */
    public function getUnpaid()
    {
        return $this->model->unpaid()->with(['customer', 'items'])->get();
    }

    /**
     * Get invoices for customer
     */
    public function getForCustomer(int $customerId)
    {
        return $this->model->where('customer_id', $customerId)
            ->with(['items', 'payments'])
            ->orderBy('invoice_date', 'desc')
            ->get();
    }

    /**
     * Get invoices for job card
     */
    public function getForJobCard(int $jobCardId)
    {
        return $this->model->where('job_card_id', $jobCardId)
            ->with(['customer', 'items', 'payments'])
            ->get();
    }

    /**
     * Get active invoices
     */
    public function getActive()
    {
        return $this->model->active()->with(['customer', 'items'])->get();
    }

    /**
     * Get invoices by date range
     */
    public function getByDateRange($startDate, $endDate)
    {
        return $this->model->whereBetween('invoice_date', [$startDate, $endDate])
            ->with(['customer', 'items', 'payments'])
            ->orderBy('invoice_date', 'desc')
            ->get();
    }
}
