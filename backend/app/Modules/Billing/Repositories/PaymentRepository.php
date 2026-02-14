<?php

namespace App\Modules\Billing\Repositories;

use App\Core\BaseRepository;
use App\Modules\Billing\Models\Payment;

class PaymentRepository extends BaseRepository
{
    public function __construct(Payment $model)
    {
        parent::__construct($model);
    }

    public function findByPaymentNumber(string $paymentNumber): ?Payment
    {
        return $this->findBy('payment_number', $paymentNumber);
    }

    public function getByInvoice(int $invoiceId)
    {
        return $this->model->where('invoice_id', $invoiceId)->get();
    }

    public function getByTenant(int $tenantId)
    {
        return $this->model->where('tenant_id', $tenantId)->get();
    }

    public function getByStatus(string $status)
    {
        return $this->model->where('status', $status)->get();
    }

    public function getByPaymentMethod(string $paymentMethod)
    {
        return $this->model->where('payment_method', $paymentMethod)->get();
    }

    public function getByDateRange(string $startDate, string $endDate)
    {
        return $this->model
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->get();
    }

    public function getTotalPayments(int $tenantId, ?string $startDate = null, ?string $endDate = null)
    {
        $query = $this->model
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed');

        if ($startDate && $endDate) {
            $query->whereBetween('payment_date', [$startDate, $endDate]);
        }

        return $query->sum('amount');
    }
}
