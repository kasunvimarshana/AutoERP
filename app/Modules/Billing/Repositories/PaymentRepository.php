<?php

namespace App\Modules\Billing\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Billing\Models\Payment;
use Illuminate\Database\Eloquent\Collection;

/**
 * Payment Repository
 * 
 * Handles data access operations for payments
 */
class PaymentRepository extends BaseRepository
{
    /**
     * Specify the model class name
     *
     * @return string
     */
    protected function model(): string
    {
        return Payment::class;
    }

    /**
     * Find payment by reference number
     *
     * @param string $referenceNumber
     * @return Payment|null
     */
    public function findByReferenceNumber(string $referenceNumber): ?Payment
    {
        return $this->model->where('reference_number', $referenceNumber)->first();
    }

    /**
     * Get payments by invoice
     *
     * @param int $invoiceId
     * @return Collection
     */
    public function findByInvoice(int $invoiceId): Collection
    {
        return $this->model
            ->where('invoice_id', $invoiceId)
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    /**
     * Get payments by customer
     *
     * @param int $customerId
     * @return Collection
     */
    public function findByCustomer(int $customerId): Collection
    {
        return $this->model
            ->where('customer_id', $customerId)
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    /**
     * Get payments by payment method
     *
     * @param string $paymentMethod
     * @return Collection
     */
    public function findByPaymentMethod(string $paymentMethod): Collection
    {
        return $this->model
            ->where('payment_method', $paymentMethod)
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    /**
     * Get total payments for a period
     *
     * @param string $startDate
     * @param string $endDate
     * @return float
     */
    public function getTotalPayments(string $startDate, string $endDate): float
    {
        return $this->model
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->sum('amount');
    }
}
