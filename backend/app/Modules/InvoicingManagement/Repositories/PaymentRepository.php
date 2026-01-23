<?php

namespace App\Modules\InvoicingManagement\Repositories;

use App\Core\Base\BaseRepository;
use App\Modules\InvoicingManagement\Models\Payment;

class PaymentRepository extends BaseRepository
{
    public function __construct(Payment $model)
    {
        parent::__construct($model);
    }

    /**
     * Search payments by various criteria
     */
    public function search(array $criteria)
    {
        $query = $this->model->query();

        if (!empty($criteria['search'])) {
            $search = $criteria['search'];
            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if (!empty($criteria['status'])) {
            $query->where('status', $criteria['status']);
        }

        if (!empty($criteria['payment_method'])) {
            $query->where('payment_method', $criteria['payment_method']);
        }

        if (!empty($criteria['invoice_id'])) {
            $query->where('invoice_id', $criteria['invoice_id']);
        }

        if (!empty($criteria['customer_id'])) {
            $query->where('customer_id', $criteria['customer_id']);
        }

        if (!empty($criteria['date_from'])) {
            $query->where('payment_date', '>=', $criteria['date_from']);
        }

        if (!empty($criteria['date_to'])) {
            $query->where('payment_date', '<=', $criteria['date_to']);
        }

        if (!empty($criteria['tenant_id'])) {
            $query->where('tenant_id', $criteria['tenant_id']);
        }

        return $query->with(['invoice', 'customer'])
            ->orderBy('payment_date', 'desc')
            ->paginate($criteria['per_page'] ?? 15);
    }

    /**
     * Find payment by payment number
     */
    public function findByPaymentNumber(string $paymentNumber): ?Payment
    {
        return $this->model->where('payment_number', $paymentNumber)->first();
    }

    /**
     * Get payments by status
     */
    public function getByStatus(string $status)
    {
        return $this->model->where('status', $status)->with(['invoice', 'customer'])->get();
    }

    /**
     * Get payments by method
     */
    public function getByMethod(string $method)
    {
        return $this->model->where('payment_method', $method)->with(['invoice', 'customer'])->get();
    }

    /**
     * Get payments for invoice
     */
    public function getForInvoice(int $invoiceId)
    {
        return $this->model->where('invoice_id', $invoiceId)
            ->with(['customer'])
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    /**
     * Get payments for customer
     */
    public function getForCustomer(int $customerId)
    {
        return $this->model->where('customer_id', $customerId)
            ->with(['invoice'])
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    /**
     * Get completed payments
     */
    public function getCompleted()
    {
        return $this->model->where('status', 'completed')->with(['invoice', 'customer'])->get();
    }

    /**
     * Get pending payments
     */
    public function getPending()
    {
        return $this->model->where('status', 'pending')->with(['invoice', 'customer'])->get();
    }

    /**
     * Get payments by date range
     */
    public function getByDateRange($startDate, $endDate)
    {
        return $this->model->whereBetween('payment_date', [$startDate, $endDate])
            ->with(['invoice', 'customer'])
            ->orderBy('payment_date', 'desc')
            ->get();
    }
}
