<?php

declare(strict_types=1);

namespace App\Modules\Payment\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Payment\Models\Payment;
use Illuminate\Database\Eloquent\Collection;

class PaymentRepository extends BaseRepository implements PaymentRepositoryInterface
{
    /**
     * PaymentRepository constructor.
     *
     * @param Payment $model
     */
    public function __construct(Payment $model)
    {
        parent::__construct($model);
    }

    /**
     * Find payment by payment number
     *
     * @param string $paymentNumber
     * @return Payment|null
     */
    public function findByPaymentNumber(string $paymentNumber): ?Payment
    {
        return $this->model->where('payment_number', $paymentNumber)->first();
    }

    /**
     * Get payments by entity
     *
     * @param string $entityType
     * @param int $entityId
     * @return Collection
     */
    public function getByEntity(string $entityType, int $entityId): Collection
    {
        return $this->model
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->with(['paymentMethod', 'allocations'])
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    /**
     * Get unreconciled payments
     *
     * @return Collection
     */
    public function getUnreconciledPayments(): Collection
    {
        return $this->model
            ->where('reconciliation_status', 'unreconciled')
            ->with(['paymentMethod', 'allocations'])
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    /**
     * Get payments by status
     *
     * @param string $status
     * @return Collection
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model
            ->where('status', $status)
            ->with(['paymentMethod', 'allocations'])
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    /**
     * Get payments by date range
     *
     * @param string $startDate
     * @param string $endDate
     * @return Collection
     */
    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        return $this->model
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->with(['paymentMethod', 'allocations'])
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    /**
     * Get payments by payment method
     *
     * @param int $paymentMethodId
     * @return Collection
     */
    public function getByPaymentMethod(int $paymentMethodId): Collection
    {
        return $this->model
            ->where('payment_method_id', $paymentMethodId)
            ->with(['paymentMethod', 'allocations'])
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    /**
     * Get total payment amount by entity
     *
     * @param string $entityType
     * @param int $entityId
     * @return string
     */
    public function getTotalByEntity(string $entityType, int $entityId): string
    {
        $total = $this->model
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('status', 'completed')
            ->sum('base_amount');

        return (string)$total;
    }

    /**
     * Search payments
     *
     * @param string $query
     * @param array $filters
     * @return Collection
     */
    public function search(string $query, array $filters = []): Collection
    {
        $queryBuilder = $this->model->query();

        // Text search
        if (!empty($query)) {
            $queryBuilder->where(function ($q) use ($query) {
                $q->where('payment_number', 'like', "%{$query}%")
                    ->orWhere('reference_number', 'like', "%{$query}%")
                    ->orWhere('transaction_id', 'like', "%{$query}%");
            });
        }

        // Apply filters
        if (!empty($filters['status'])) {
            $queryBuilder->where('status', $filters['status']);
        }

        if (!empty($filters['payment_type'])) {
            $queryBuilder->where('payment_type', $filters['payment_type']);
        }

        if (!empty($filters['payment_method_id'])) {
            $queryBuilder->where('payment_method_id', $filters['payment_method_id']);
        }

        if (!empty($filters['entity_type'])) {
            $queryBuilder->where('entity_type', $filters['entity_type']);
        }

        if (!empty($filters['reconciliation_status'])) {
            $queryBuilder->where('reconciliation_status', $filters['reconciliation_status']);
        }

        if (!empty($filters['start_date'])) {
            $queryBuilder->whereDate('payment_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $queryBuilder->whereDate('payment_date', '<=', $filters['end_date']);
        }

        return $queryBuilder
            ->with(['paymentMethod', 'allocations'])
            ->orderBy('payment_date', 'desc')
            ->get();
    }
}
