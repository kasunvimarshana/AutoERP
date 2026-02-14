<?php

namespace App\Modules\Payment\Repositories;

use App\Base\BaseRepository;
use App\Modules\Payment\Models\PaymentRefund;
use Illuminate\Database\Eloquent\Collection;

class PaymentRefundRepository extends BaseRepository
{
    public function __construct(PaymentRefund $model)
    {
        parent::__construct($model);
    }

    public function findByPayment(string $paymentId, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('payment_id', $paymentId)
            ->orderBy('refund_date', 'desc')
            ->get();
    }

    public function getByStatus(string $status, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('status', $status)
            ->orderBy('refund_date', 'desc')
            ->get();
    }

    public function getTotalRefunds(string $from, string $to): float
    {
        return (float) $this->model
            ->where('status', 'completed')
            ->whereBetween('refund_date', [$from, $to])
            ->sum('amount');
    }
}
