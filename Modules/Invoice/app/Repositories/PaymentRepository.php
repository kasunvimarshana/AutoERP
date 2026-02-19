<?php

declare(strict_types=1);

namespace Modules\Invoice\Repositories;

use App\Core\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Invoice\Models\Payment;

/**
 * Payment Repository
 *
 * Handles data access for Payment model
 */
class PaymentRepository extends BaseRepository
{
    /**
     * {@inheritDoc}
     */
    protected function makeModel(): Model
    {
        return new Payment;
    }

    /**
     * Find payment by payment number
     */
    public function findByPaymentNumber(string $paymentNumber): ?Payment
    {
        /** @var Payment|null */
        return $this->findOneBy(['payment_number' => $paymentNumber]);
    }

    /**
     * Check if payment number exists
     */
    public function paymentNumberExists(string $paymentNumber, ?int $excludeId = null): bool
    {
        $query = $this->model->newQuery()->where('payment_number', $paymentNumber);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get payments for invoice
     */
    public function getForInvoice(int $invoiceId): Collection
    {
        return $this->model->newQuery()
            ->where('invoice_id', $invoiceId)
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    /**
     * Get payments by method
     */
    public function getByMethod(string $method): Collection
    {
        return $this->model->newQuery()->where('payment_method', $method)->get();
    }

    /**
     * Get payments by status
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->newQuery()->where('status', $status)->get();
    }

    /**
     * Get payment with relations
     */
    public function findWithRelations(int $id): ?Payment
    {
        /** @var Payment|null */
        return $this->model->newQuery()
            ->with(['invoice', 'processedBy'])
            ->find($id);
    }

    /**
     * Get payment history with filters
     *
     * @param  array<string, mixed>  $filters
     */
    public function getHistory(array $filters = []): Collection
    {
        $query = $this->model->newQuery()->with(['invoice.customer', 'processedBy']);

        if (isset($filters['invoice_id'])) {
            $query->where('invoice_id', $filters['invoice_id']);
        }

        if (isset($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['from_date'])) {
            $query->where('payment_date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('payment_date', '<=', $filters['to_date']);
        }

        return $query->orderBy('payment_date', 'desc')->get();
    }
}
