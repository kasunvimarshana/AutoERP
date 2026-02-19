<?php

declare(strict_types=1);

namespace Modules\Billing\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Billing\Models\SubscriptionPayment;
use Modules\Core\Repositories\BaseRepository;

class SubscriptionPaymentRepository extends BaseRepository
{
    protected function makeModel(): Model
    {
        return new SubscriptionPayment;
    }

    public function findByCode(string $code): ?SubscriptionPayment
    {
        return $this->model->where('payment_code', $code)->first();
    }

    public function findByTransactionId(string $transactionId): ?SubscriptionPayment
    {
        return $this->model->where('transaction_id', $transactionId)->first();
    }

    public function getBySubscription(int $subscriptionId): Collection
    {
        return $this->model
            ->where('subscription_id', $subscriptionId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getSuccessfulPayments(int $subscriptionId): Collection
    {
        return $this->model
            ->where('subscription_id', $subscriptionId)
            ->where('status', 'succeeded')
            ->orderBy('paid_at', 'desc')
            ->get();
    }

    public function searchPayments(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['subscription_id'])) {
            $query->where('subscription_id', $filters['subscription_id']);
        }

        if (isset($filters['payment_gateway'])) {
            $query->where('payment_gateway', $filters['payment_gateway']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('payment_code', 'like', "%{$search}%")
                    ->orWhere('transaction_id', 'like', "%{$search}%");
            });
        }

        return $query
            ->with('subscription.plan')
            ->latest()
            ->paginate($perPage);
    }
}
