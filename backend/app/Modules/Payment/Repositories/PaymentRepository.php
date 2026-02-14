<?php

namespace App\Modules\Payment\Repositories;

use App\Base\BaseRepository;
use App\Modules\Payment\Models\Payment;
use Illuminate\Database\Eloquent\Collection;

class PaymentRepository extends BaseRepository
{
    public function __construct(Payment $model)
    {
        parent::__construct($model);
    }

    public function findByPaymentNumber(string $paymentNumber): ?Payment
    {
        return $this->model->where('payment_number', $paymentNumber)->first();
    }

    public function findByCustomer(string $customerId, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('customer_id', $customerId)
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    public function findByInvoice(string $invoiceId, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('invoice_id', $invoiceId)
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    public function findByTenant(string $tenantId, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('tenant_id', $tenantId)
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    public function findByBranch(string $branchId, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('branch_id', $branchId)
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    public function findByTransactionId(string $transactionId): ?Payment
    {
        return $this->model->where('transaction_id', $transactionId)->first();
    }

    public function getByStatus(string $status, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('status', $status)
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    public function getByPaymentMethod(string $method, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('payment_method', $method)
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    public function getByDateRange(string $from, string $to, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->whereBetween('payment_date', [$from, $to])
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    public function getTotalByMethod(string $method, string $from, string $to): float
    {
        return (float) $this->model
            ->where('payment_method', $method)
            ->where('status', 'completed')
            ->whereBetween('payment_date', [$from, $to])
            ->sum('amount');
    }
}
