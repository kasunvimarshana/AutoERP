<?php

namespace App\Modules\POS\Services;

use App\Core\Services\BaseService;
use App\Modules\Inventory\Services\StockService;
use App\Modules\POS\Repositories\POSTransactionRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class POSService extends BaseService
{
    protected StockService $stockService;

    /**
     * POSService constructor
     */
    public function __construct(
        POSTransactionRepository $repository,
        StockService $stockService
    ) {
        $this->repository = $repository;
        $this->stockService = $stockService;
    }

    /**
     * Process checkout
     */
    public function checkout(array $data): mixed
    {
        DB::beginTransaction();

        try {
            $totalAmount = 0;
            $items = $data['items'] ?? [];

            foreach ($items as $item) {
                $totalAmount += $item['quantity'] * $item['price'];
            }

            $transactionData = [
                'transaction_number' => $this->generateTransactionNumber(),
                'cashier_id' => $data['cashier_id'],
                'branch_id' => $data['branch_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'subtotal' => $data['subtotal'] ?? $totalAmount,
                'tax_amount' => $data['tax_amount'] ?? 0,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'total_amount' => $data['total_amount'] ?? $totalAmount,
                'payment_method' => $data['payment_method'],
                'payment_received' => $data['payment_received'],
                'change_amount' => $data['change_amount'] ?? 0,
                'status' => 'completed',
                'completed_at' => now(),
            ];

            $transaction = $this->repository->create($transactionData);

            foreach ($items as $item) {
                if (isset($item['product_id']) && isset($data['warehouse_id'])) {
                    $this->stockService->adjustStock(
                        $item['product_id'],
                        $data['warehouse_id'],
                        $item['quantity'],
                        'out',
                        "POS Sale - Transaction #{$transaction->transaction_number}"
                    );
                }
            }

            DB::commit();

            Log::info("POS transaction {$transaction->transaction_number} completed");

            return $transaction;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing checkout: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Void transaction
     */
    public function voidTransaction(int $transactionId, string $reason): bool
    {
        DB::beginTransaction();

        try {
            $transaction = $this->repository->findOrFail($transactionId);

            if ($transaction->status === 'voided') {
                throw new \Exception('Transaction is already voided');
            }

            $result = $this->repository->update($transactionId, [
                'status' => 'voided',
                'voided_at' => now(),
                'void_reason' => $reason,
            ]);

            DB::commit();

            Log::info("Transaction {$transactionId} voided: {$reason}");

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error voiding transaction {$transactionId}: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get daily sales report
     */
    public function getDailySales(?string $date = null, ?int $branchId = null): array
    {
        try {
            $date = $date ?? now()->toDateString();

            $total = $this->repository->getDailySalesTotal($date, $branchId);
            $transactions = $this->repository->getByDateRange(
                $date.' 00:00:00',
                $date.' 23:59:59'
            );

            if ($branchId) {
                $transactions = $transactions->where('branch_id', $branchId);
            }

            $completed = $transactions->where('status', 'completed')->count();
            $voided = $transactions->where('status', 'voided')->count();

            return [
                'date' => $date,
                'branch_id' => $branchId,
                'total_sales' => $total,
                'total_transactions' => $transactions->count(),
                'completed_transactions' => $completed,
                'voided_transactions' => $voided,
                'average_transaction_value' => $completed > 0 ? $total / $completed : 0,
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching daily sales: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate unique transaction number
     */
    private function generateTransactionNumber(): string
    {
        return 'POS-'.date('Ymd').'-'.str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get transactions by date range
     */
    public function getByDateRange(string $startDate, string $endDate)
    {
        try {
            return $this->repository->getByDateRange($startDate, $endDate);
        } catch (\Exception $e) {
            Log::error('Error fetching transactions by date range: '.$e->getMessage());
            throw $e;
        }
    }
}
