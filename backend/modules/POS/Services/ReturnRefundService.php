<?php

declare(strict_types=1);

namespace Modules\POS\Services;

use Modules\POS\Models\Transaction;
use Modules\POS\Enums\TransactionType;
use Modules\POS\Enums\TransactionStatus;
use Modules\POS\Enums\PaymentStatus;
use Modules\Inventory\Services\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Return & Refund Service
 * 
 * Handles sales returns and refund processing for POS transactions.
 * Supports full and partial returns with stock restoration.
 */
class ReturnRefundService
{
    public function __construct(
        private TransactionService $transactionService,
        private StockService $stockService,
        private ReceiptService $receiptService
    ) {}

    /**
     * Process sales return
     *
     * @param string $originalTransactionId
     * @param array $returnData
     * @return array
     * @throws InvalidArgumentException
     */
    public function processReturn(string $originalTransactionId, array $returnData): array
    {
        $originalTransaction = Transaction::with(['lines', 'payments'])
            ->findOrFail($originalTransactionId);
        
        $this->validateReturnData($returnData, $originalTransaction);
        
        return DB::transaction(function () use ($originalTransaction, $returnData) {
            // Create return transaction
            $returnTransaction = $this->createReturnTransaction($originalTransaction, $returnData);
            
            // Restore stock
            $this->restoreStock($returnTransaction);
            
            // Process refund
            $refund = $this->processRefund($returnTransaction, $returnData);
            
            // Update original transaction
            $this->updateOriginalTransaction($originalTransaction, $returnTransaction);
            
            // Generate return receipt
            $receipt = $this->receiptService->generateReceipt($returnTransaction, 'thermal');
            
            Log::info('Return processed', [
                'original_transaction_id' => $originalTransaction->id,
                'return_transaction_id' => $returnTransaction->id,
                'refund_amount' => $refund['amount'],
            ]);
            
            return [
                'success' => true,
                'return_transaction' => $returnTransaction,
                'refund' => $refund,
                'receipt' => $receipt,
            ];
        });
    }

    /**
     * Process full return (all items)
     *
     * @param string $originalTransactionId
     * @param array $refundData
     * @return array
     */
    public function processFullReturn(string $originalTransactionId, array $refundData): array
    {
        $originalTransaction = Transaction::with('lines')->findOrFail($originalTransactionId);
        
        // Check if there are already returns for this transaction
        $returnHistory = $this->getReturnHistory($originalTransactionId);
        if (!empty($returnHistory)) {
            throw new InvalidArgumentException('This transaction already has returns. Use partial return instead.');
        }
        
        // Build return data with all items
        $returnLines = $originalTransaction->lines->map(function ($line) {
            return [
                'product_id' => $line->product_id,
                'variation_id' => $line->variation_id,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'discount_amount' => $line->discount_amount,
                'tax_amount' => $line->tax_amount,
                'line_total' => $line->line_total,
            ];
        })->toArray();
        
        return $this->processReturn($originalTransactionId, [
            'lines' => $returnLines,
            'reason' => $refundData['reason'] ?? 'Full return',
            'notes' => $refundData['notes'] ?? null,
            'payment_method_id' => $refundData['payment_method_id'] ?? 'cash',
        ]);
    }

    /**
     * Create return transaction
     *
     * @param Transaction $originalTransaction
     * @param array $returnData
     * @return Transaction
     */
    private function createReturnTransaction(Transaction $originalTransaction, array $returnData): Transaction
    {
        $totals = $this->calculateReturnTotals($returnData['lines']);
        
        return $this->transactionService->createTransaction([
            'location_id' => $originalTransaction->location_id,
            'cash_register_id' => $originalTransaction->cash_register_id,
            'type' => TransactionType::SELL_RETURN,
            'status' => TransactionStatus::COMPLETED,
            'contact_id' => $originalTransaction->contact_id,
            'transaction_date' => now(),
            'lines' => $returnData['lines'],
            'subtotal' => $totals['subtotal'],
            'tax_amount' => $totals['tax_amount'],
            'discount_amount' => $totals['discount_amount'],
            'total_amount' => $totals['total_amount'],
            'payment_status' => PaymentStatus::PAID,
            'paid_amount' => $totals['total_amount'],
            'notes' => $returnData['notes'] ?? null,
            'additional_data' => [
                'original_transaction_id' => $originalTransaction->id,
                'return_reason' => $returnData['reason'] ?? null,
            ],
        ]);
    }

    /**
     * Calculate return totals
     *
     * @param array $lines
     * @return array
     */
    private function calculateReturnTotals(array $lines): array
    {
        $subtotal = 0;
        $totalTax = 0;
        $totalDiscount = 0;
        
        foreach ($lines as $line) {
            $lineSubtotal = $line['quantity'] * $line['unit_price'];
            $lineDiscount = $line['discount_amount'] ?? 0;
            $lineTax = $line['tax_amount'] ?? 0;
            
            $subtotal += $lineSubtotal;
            $totalDiscount += $lineDiscount;
            $totalTax += $lineTax;
        }
        
        return [
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($totalDiscount, 2),
            'tax_amount' => round($totalTax, 2),
            'total_amount' => round($subtotal - $totalDiscount + $totalTax, 2),
        ];
    }

    /**
     * Restore stock for returned items
     *
     * @param Transaction $returnTransaction
     */
    private function restoreStock(Transaction $returnTransaction): void
    {
        foreach ($returnTransaction->lines as $line) {
            $this->stockService->increaseStock(
                $line->product_id,
                $returnTransaction->location_id,
                $line->quantity,
                $line->variation_id,
                "Sales Return: {$returnTransaction->transaction_number}"
            );
        }
    }

    /**
     * Process refund payment
     *
     * @param Transaction $returnTransaction
     * @param array $returnData
     * @return array
     */
    private function processRefund(Transaction $returnTransaction, array $returnData): array
    {
        $refundAmount = $returnTransaction->total_amount;
        
        // Create refund payment record
        $payment = $this->transactionService->addPayment($returnTransaction, [
            'payment_method_id' => $returnData['payment_method_id'] ?? 'cash',
            'amount' => $refundAmount,
            'payment_date' => now(),
            'notes' => 'Refund for sales return',
        ]);
        
        return [
            'payment_id' => $payment->id,
            'amount' => $refundAmount,
            'method' => $returnData['payment_method_id'] ?? 'cash',
            'status' => 'refunded',
        ];
    }

    /**
     * Update original transaction with return reference
     *
     * @param Transaction $originalTransaction
     * @param Transaction $returnTransaction
     */
    private function updateOriginalTransaction(Transaction $originalTransaction, Transaction $returnTransaction): void
    {
        $additionalData = $originalTransaction->additional_data ?? [];
        $additionalData['return_transactions'] = $additionalData['return_transactions'] ?? [];
        $additionalData['return_transactions'][] = [
            'id' => $returnTransaction->id,
            'amount' => $returnTransaction->total_amount,
            'date' => $returnTransaction->transaction_date->toDateTimeString(),
        ];
        
        $originalTransaction->update([
            'additional_data' => $additionalData,
        ]);
    }

    /**
     * Validate return data
     *
     * @param array $returnData
     * @param Transaction $originalTransaction
     * @throws InvalidArgumentException
     */
    private function validateReturnData(array $returnData, Transaction $originalTransaction): void
    {
        if (empty($returnData['lines']) || !is_array($returnData['lines'])) {
            throw new InvalidArgumentException('Return items are required');
        }
        
        // Validate original transaction is a sale
        if ($originalTransaction->type !== TransactionType::SALE) {
            throw new InvalidArgumentException('Can only return sale transactions');
        }
        
        // Validate original transaction is completed
        if ($originalTransaction->status !== TransactionStatus::COMPLETED) {
            throw new InvalidArgumentException('Original transaction must be completed');
        }
        
        // Get previously returned quantities
        $returnHistory = $this->getReturnHistory($originalTransaction->id);
        $returnedQuantities = [];
        foreach ($returnHistory as $return) {
            foreach ($return['lines'] as $line) {
                $key = $line['product_id'] . '_' . ($line['variation_id'] ?? 'null');
                $returnedQuantities[$key] = ($returnedQuantities[$key] ?? 0) + $line['quantity'];
            }
        }
        
        // Validate return quantities don't exceed available quantities
        foreach ($returnData['lines'] as $returnLine) {
            $originalLine = $originalTransaction->lines->first(function ($line) use ($returnLine) {
                return $line->product_id === $returnLine['product_id'] &&
                       ($line->variation_id ?? null) === ($returnLine['variation_id'] ?? null);
            });
            
            if (!$originalLine) {
                throw new InvalidArgumentException(
                    "Product {$returnLine['product_id']} not found in original transaction"
                );
            }
            
            // Check previously returned quantities
            $key = $returnLine['product_id'] . '_' . ($returnLine['variation_id'] ?? 'null');
            $alreadyReturned = $returnedQuantities[$key] ?? 0;
            $availableToReturn = $originalLine->quantity - $alreadyReturned;
            
            if ($returnLine['quantity'] > $availableToReturn) {
                throw new InvalidArgumentException(
                    "Return quantity exceeds available quantity for product {$returnLine['product_id']}. " .
                    "Original: {$originalLine->quantity}, Already returned: {$alreadyReturned}, " .
                    "Available: {$availableToReturn}, Requested: {$returnLine['quantity']}"
                );
            }
        }
    }

    /**
     * Check if return is allowed for transaction
     *
     * @param string $transactionId
     * @param int $returnWindowDays
     * @return bool
     */
    public function isReturnAllowed(string $transactionId, int $returnWindowDays = 30): bool
    {
        $transaction = Transaction::find($transactionId);
        
        if (!$transaction) {
            return false;
        }
        
        // Check transaction type
        if ($transaction->type !== TransactionType::SALE) {
            return false;
        }
        
        // Check transaction status
        if ($transaction->status !== TransactionStatus::COMPLETED) {
            return false;
        }
        
        // Check return window
        $daysSincePurchase = now()->diffInDays($transaction->transaction_date);
        if ($daysSincePurchase > $returnWindowDays) {
            return false;
        }
        
        return true;
    }

    /**
     * Get return history for a transaction
     *
     * @param string $transactionId
     * @return array
     */
    public function getReturnHistory(string $transactionId): array
    {
        return Transaction::where('type', TransactionType::SELL_RETURN)
            ->where('additional_data->original_transaction_id', $transactionId)
            ->with(['lines', 'payments'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }
}
