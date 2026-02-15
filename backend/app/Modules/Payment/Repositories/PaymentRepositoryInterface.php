<?php

declare(strict_types=1);

namespace App\Modules\Payment\Repositories;

use App\Modules\Payment\Models\Payment;
use Illuminate\Database\Eloquent\Collection;

interface PaymentRepositoryInterface
{
    /**
     * Find payment by payment number
     *
     * @param string $paymentNumber
     * @return Payment|null
     */
    public function findByPaymentNumber(string $paymentNumber): ?Payment;

    /**
     * Get payments by entity
     *
     * @param string $entityType
     * @param int $entityId
     * @return Collection
     */
    public function getByEntity(string $entityType, int $entityId): Collection;

    /**
     * Get unreconciled payments
     *
     * @return Collection
     */
    public function getUnreconciledPayments(): Collection;

    /**
     * Get payments by status
     *
     * @param string $status
     * @return Collection
     */
    public function getByStatus(string $status): Collection;

    /**
     * Get payments by date range
     *
     * @param string $startDate
     * @param string $endDate
     * @return Collection
     */
    public function getByDateRange(string $startDate, string $endDate): Collection;

    /**
     * Get payments by payment method
     *
     * @param int $paymentMethodId
     * @return Collection
     */
    public function getByPaymentMethod(int $paymentMethodId): Collection;

    /**
     * Get total payment amount by entity
     *
     * @param string $entityType
     * @param int $entityId
     * @return string
     */
    public function getTotalByEntity(string $entityType, int $entityId): string;

    /**
     * Search payments
     *
     * @param string $query
     * @param array $filters
     * @return Collection
     */
    public function search(string $query, array $filters = []): Collection;
}
