<?php

namespace App\Modules\Billing\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Billing\Models\Payment;
use Illuminate\Database\Eloquent\Collection;

class PaymentRepository extends BaseRepository
{
    /**
     * Specify Model class name
     */
    protected function model(): string
    {
        return Payment::class;
    }

    /**
     * Get payments by invoice
     */
    public function getByInvoice(int $invoiceId): Collection
    {
        return $this->model->where('invoice_id', $invoiceId)
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    /**
     * Get payments by customer
     */
    public function getByCustomer(int $customerId): Collection
    {
        return $this->model->where('customer_id', $customerId)
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    /**
     * Get payments by method
     */
    public function getByMethod(string $method): Collection
    {
        return $this->model->where('payment_method', $method)
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    /**
     * Get payments by date range
     */
    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        return $this->model->whereBetween('payment_date', [$startDate, $endDate])
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    /**
     * Get total payments by invoice
     */
    public function getTotalByInvoice(int $invoiceId): float
    {
        return $this->model->where('invoice_id', $invoiceId)
            ->where('status', 'completed')
            ->sum('amount');
    }
}
