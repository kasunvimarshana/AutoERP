<?php

namespace App\Modules\POS\Repositories;

use App\Core\BaseRepository;
use App\Modules\POS\Models\POSTransaction;

class POSTransactionRepository extends BaseRepository
{
    public function __construct(POSTransaction $model)
    {
        parent::__construct($model);
    }

    public function findByTransactionNumber(string $transactionNumber): ?POSTransaction
    {
        return $this->findBy('transaction_number', $transactionNumber);
    }

    public function getByTenant(int $tenantId)
    {
        return $this->model->where('tenant_id', $tenantId)->get();
    }

    public function getByCustomer(int $customerId)
    {
        return $this->model->where('customer_id', $customerId)->get();
    }

    public function getByCashier(int $cashierId)
    {
        return $this->model->where('cashier_id', $cashierId)->get();
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
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->get();
    }

    public function getTotalSales(int $tenantId, ?string $startDate = null, ?string $endDate = null)
    {
        $query = $this->model
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed');

        if ($startDate && $endDate) {
            $query->whereBetween('transaction_date', [$startDate, $endDate]);
        }

        return $query->sum('total_amount');
    }

    public function getSalesByPaymentMethod(int $tenantId, ?string $startDate = null, ?string $endDate = null)
    {
        $query = $this->model
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed');

        if ($startDate && $endDate) {
            $query->whereBetween('transaction_date', [$startDate, $endDate]);
        }

        return $query
            ->selectRaw('payment_method, SUM(total_amount) as total')
            ->groupBy('payment_method')
            ->get();
    }
}
