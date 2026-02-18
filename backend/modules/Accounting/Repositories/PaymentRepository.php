<?php

declare(strict_types=1);

namespace Modules\Accounting\Repositories;

use Modules\Accounting\Models\Payment;
use Modules\Core\Repositories\BaseRepository;

/**
 * Payment Repository
 */
class PaymentRepository extends BaseRepository
{
    protected function model(): string
    {
        return Payment::class;
    }

    public function findByPaymentNumber(string $paymentNumber): ?Payment
    {
        return $this->newQuery()->where('payment_number', $paymentNumber)->first();
    }

    public function getCompletedPayments(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->newQuery()
            ->where('status', 'completed')
            ->orderBy('payment_date', 'desc')
            ->get();
    }
}
