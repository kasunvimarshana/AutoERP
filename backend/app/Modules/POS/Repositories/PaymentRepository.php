<?php

namespace App\Modules\POS\Repositories;

use App\Modules\POS\Models\Payment;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

class PaymentRepository extends BaseRepository
{
    protected function model(): string
    {
        return Payment::class;
    }

    public function findByInvoice(int $invoiceId): Collection
    {
        return $this->model
            ->where('invoice_id', $invoiceId)
            ->orderBy('payment_date', 'desc')
            ->get();
    }

    public function findByCustomer(int $customerId, array $filters = []): Collection
    {
        $query = $this->model->where('customer_id', $customerId);

        if (isset($filters['from_date'])) {
            $query->where('payment_date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('payment_date', '<=', $filters['to_date']);
        }

        if (isset($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        return $query->with(['invoice', 'customer'])->orderBy('payment_date', 'desc')->get();
    }

    public function generatePaymentNumber(): string
    {
        $lastPayment = $this->model->orderBy('id', 'desc')->first();
        $lastNumber = $lastPayment ? intval(substr($lastPayment->payment_number, 4)) : 0;

        return 'PAY-'.str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
    }
}
