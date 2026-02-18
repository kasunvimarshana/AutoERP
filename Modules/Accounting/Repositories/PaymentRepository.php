<?php

declare(strict_types=1);

namespace Modules\Accounting\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Accounting\Entities\Payment;
use Modules\Accounting\Repositories\Contracts\PaymentRepositoryInterface;
use Modules\Core\Repositories\BaseRepository;

/**
 * Payment Repository Implementation
 *
 * Handles all payment data access operations.
 */
class PaymentRepository extends BaseRepository implements PaymentRepositoryInterface
{
    /**
     * PaymentRepository constructor.
     */
    public function __construct(Payment $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function findByPaymentNumber(string $paymentNumber): ?Payment
    {
        return $this->model->where('payment_number', $paymentNumber)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getByCustomer(int $customerId): Collection
    {
        return $this->model
            ->where('customer_id', $customerId)
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getByInvoice(int $invoiceId): Collection
    {
        return $this->model
            ->where('invoice_id', $invoiceId)
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->orderBy('payment_date', 'desc')->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): Collection
    {
        return $this->model
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getByPaymentMethod(string $method): Collection
    {
        return $this->model
            ->where('payment_method', $method)
            ->orderBy('payment_date', 'desc')
            ->get();
    }
}
