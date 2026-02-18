<?php

declare(strict_types=1);

namespace Modules\Accounting\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Accounting\Entities\Payment;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;

/**
 * Payment Repository Interface
 *
 * Defines the contract for payment data access operations.
 */
interface PaymentRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find payment by payment number.
     */
    public function findByPaymentNumber(string $paymentNumber): ?Payment;

    /**
     * Get payments by customer.
     */
    public function getByCustomer(int $customerId): Collection;

    /**
     * Get payments by invoice.
     */
    public function getByInvoice(int $invoiceId): Collection;

    /**
     * Get payments by status.
     */
    public function getByStatus(string $status): Collection;

    /**
     * Get payments by date range.
     */
    public function getByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): Collection;

    /**
     * Get payments by payment method.
     */
    public function getByPaymentMethod(string $method): Collection;
}
