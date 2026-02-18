<?php

declare(strict_types=1);

namespace Modules\POS\Services;

use Modules\POS\Models\Transaction;
use Modules\POS\Enums\TransactionStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Sale Suspension Service
 * 
 * Handles pausing/suspending and resuming sales transactions.
 * Allows cashiers to temporarily set aside a transaction to serve other customers.
 */
class SaleSuspensionService
{
    public function __construct(
        private TransactionService $transactionService,
        private POSCheckoutService $checkoutService
    ) {}

    /**
     * Suspend/pause a sale transaction
     *
     * @param array $saleData
     * @return Transaction
     */
    public function suspendSale(array $saleData): Transaction
    {
        $transaction = $this->transactionService->createTransaction([
            'location_id' => $saleData['location_id'],
            'cash_register_id' => $saleData['cash_register_id'],
            'type' => \Modules\POS\Enums\TransactionType::SALE,
            'status' => TransactionStatus::SUSPENDED,
            'contact_id' => $saleData['customer_id'] ?? null,
            'lines' => $saleData['lines'],
            'subtotal' => $saleData['subtotal'] ?? 0,
            'tax_amount' => $saleData['tax_amount'] ?? 0,
            'discount_amount' => $saleData['discount_amount'] ?? 0,
            'total_amount' => $saleData['total_amount'] ?? 0,
            'notes' => $saleData['notes'] ?? 'Suspended sale',
            'additional_data' => [
                'suspended_by' => auth()->id(),
                'suspended_at' => now()->toDateTimeString(),
                'suspension_reason' => $saleData['suspension_reason'] ?? null,
            ],
        ]);
        
        Log::info('Sale suspended', [
            'transaction_id' => $transaction->id,
            'suspended_by' => auth()->id(),
        ]);
        
        return $transaction;
    }

    /**
     * Resume a suspended sale
     *
     * @param string $transactionId
     * @return Transaction
     * @throws InvalidArgumentException
     */
    public function resumeSale(string $transactionId): Transaction
    {
        $transaction = Transaction::with('lines')->findOrFail($transactionId);
        
        if ($transaction->status !== TransactionStatus::SUSPENDED) {
            throw new InvalidArgumentException('Transaction is not suspended');
        }
        
        $transaction->update([
            'status' => TransactionStatus::DRAFT,
            'additional_data' => array_merge(
                $transaction->additional_data ?? [],
                [
                    'resumed_by' => auth()->id(),
                    'resumed_at' => now()->toDateTimeString(),
                ]
            ),
        ]);
        
        Log::info('Sale resumed', [
            'transaction_id' => $transaction->id,
            'resumed_by' => auth()->id(),
        ]);
        
        return $transaction->fresh('lines');
    }

    /**
     * Complete a suspended sale with payment
     *
     * @param string $transactionId
     * @param array $checkoutData
     * @return array
     */
    public function completeSuspendedSale(string $transactionId, array $checkoutData): array
    {
        $transaction = $this->resumeSale($transactionId);
        
        // Use the checkout service to complete the sale
        return $this->checkoutService->resumeDraft($transaction, $checkoutData);
    }

    /**
     * Cancel a suspended sale
     *
     * @param string $transactionId
     * @param string|null $reason
     * @return bool
     */
    public function cancelSuspendedSale(string $transactionId, ?string $reason = null): bool
    {
        $transaction = Transaction::findOrFail($transactionId);
        
        if ($transaction->status !== TransactionStatus::SUSPENDED) {
            throw new InvalidArgumentException('Transaction is not suspended');
        }
        
        $transaction->update([
            'status' => TransactionStatus::CANCELLED,
            'additional_data' => array_merge(
                $transaction->additional_data ?? [],
                [
                    'cancelled_by' => auth()->id(),
                    'cancelled_at' => now()->toDateTimeString(),
                    'cancellation_reason' => $reason,
                ]
            ),
        ]);
        
        Log::info('Suspended sale cancelled', [
            'transaction_id' => $transaction->id,
            'cancelled_by' => auth()->id(),
            'reason' => $reason,
        ]);
        
        return true;
    }

    /**
     * Get all suspended sales for a cash register
     *
     * @param string $cashRegisterId
     * @return Collection
     */
    public function getSuspendedSales(string $cashRegisterId): Collection
    {
        return Transaction::with(['lines.product', 'contact'])
            ->where('cash_register_id', $cashRegisterId)
            ->where('status', TransactionStatus::SUSPENDED)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get all suspended sales for a location
     *
     * @param string $locationId
     * @return Collection
     */
    public function getSuspendedSalesByLocation(string $locationId): Collection
    {
        return Transaction::with(['lines.product', 'contact', 'cashRegister'])
            ->where('location_id', $locationId)
            ->where('status', TransactionStatus::SUSPENDED)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get count of suspended sales for a cash register
     *
     * @param string $cashRegisterId
     * @return int
     */
    public function getSuspendedSalesCount(string $cashRegisterId): int
    {
        return Transaction::where('cash_register_id', $cashRegisterId)
            ->where('status', TransactionStatus::SUSPENDED)
            ->count();
    }

    /**
     * Check if sale can be suspended
     *
     * @param array $saleData
     * @return bool
     */
    public function canSuspendSale(array $saleData): bool
    {
        // Validate minimum requirements for suspension
        if (empty($saleData['lines']) || !is_array($saleData['lines'])) {
            return false;
        }
        
        if (empty($saleData['cash_register_id'])) {
            return false;
        }
        
        // Check if there's room for more suspended sales (optional limit)
        $suspendedCount = $this->getSuspendedSalesCount($saleData['cash_register_id']);
        $maxSuspendedSales = config('pos.max_suspended_sales', 50);
        
        return $suspendedCount < $maxSuspendedSales;
    }

    /**
     * Get suspended sale details
     *
     * @param string $transactionId
     * @return array
     */
    public function getSuspendedSaleDetails(string $transactionId): array
    {
        $transaction = Transaction::with(['lines.product', 'contact', 'cashRegister'])
            ->findOrFail($transactionId);
        
        if ($transaction->status !== TransactionStatus::SUSPENDED) {
            throw new InvalidArgumentException('Transaction is not suspended');
        }
        
        return [
            'transaction' => $transaction,
            'items_count' => $transaction->lines->count(),
            'suspended_duration' => now()->diffForHumans($transaction->created_at),
            'suspended_by' => $transaction->additional_data['suspended_by'] ?? null,
            'suspension_reason' => $transaction->additional_data['suspension_reason'] ?? null,
        ];
    }
}
