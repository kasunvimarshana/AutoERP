<?php

declare(strict_types=1);

namespace App\Modules\POS\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\POS\Models\POSTransaction;
use Illuminate\Database\Eloquent\Collection;

class POSTransactionRepository extends BaseRepository implements POSTransactionRepositoryInterface
{
    public function __construct(POSTransaction $model)
    {
        parent::__construct($model);
    }

    public function findByTransactionNumber(string $transactionNumber): ?POSTransaction
    {
        return $this->model->where('transaction_number', $transactionNumber)
            ->with(['items.product', 'customer', 'session', 'payment'])
            ->first();
    }

    public function getBySession(int $sessionId): Collection
    {
        return $this->model->where('session_id', $sessionId)
            ->with(['items.product', 'customer'])
            ->orderBy('transaction_date', 'desc')
            ->get();
    }

    public function getByCustomer(int $customerId): Collection
    {
        return $this->model->where('customer_id', $customerId)
            ->with(['items.product', 'session'])
            ->orderBy('transaction_date', 'desc')
            ->get();
    }

    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        return $this->model->whereBetween('transaction_date', [$startDate, $endDate])
            ->with(['items.product', 'customer', 'session'])
            ->orderBy('transaction_date', 'desc')
            ->get();
    }

    public function getTotalBySession(int $sessionId): array
    {
        $transactions = $this->model->where('session_id', $sessionId)
            ->where('status', 'completed')
            ->with('paymentMethod')
            ->get();

        $totalSales = $transactions->sum('total_amount');
        
        // Calculate totals by payment method type dynamically
        $totalCash = $transactions->filter(function ($t) {
            return $t->paymentMethod && $t->paymentMethod->type === 'cash';
        })->sum('total_amount');
        
        $totalCard = $transactions->filter(function ($t) {
            return $t->paymentMethod && in_array($t->paymentMethod->type, ['credit_card', 'debit_card']);
        })->sum('total_amount');

        return [
            'total_sales' => (string) $totalSales,
            'total_cash' => (string) $totalCash,
            'total_card' => (string) $totalCard,
            'transaction_count' => $transactions->count(),
        ];
    }
}
