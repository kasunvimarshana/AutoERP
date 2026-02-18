<?php

declare(strict_types=1);

namespace Modules\POS\Services;

use Modules\POS\Models\Transaction;
use Modules\POS\Models\CashRegister;
use Modules\POS\Enums\TransactionType;
use Modules\POS\Enums\TransactionStatus;
use Modules\POS\Enums\PaymentStatus;
use Modules\Inventory\Services\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * POS Checkout Service
 * 
 * Handles fast checkout workflow for POS sales transactions.
 * Includes validation, inventory checking, and transaction creation.
 */
class POSCheckoutService
{
    public function __construct(
        private TransactionService $transactionService,
        private ReceiptService $receiptService,
        private StockService $stockService,
        private CashRegisterService $cashRegisterService
    ) {}

    /**
     * Process quick checkout
     *
     * @param array $checkoutData
     * @return array
     * @throws InvalidArgumentException
     */
    public function processCheckout(array $checkoutData): array
    {
        $this->validateCheckoutData($checkoutData);
        
        return DB::transaction(function () use ($checkoutData) {
            // Validate cash register is open
            $cashRegister = $this->validateCashRegister($checkoutData['cash_register_id']);
            
            // Check stock availability
            $this->validateStockAvailability($checkoutData['lines'], $checkoutData['location_id']);
            
            // Calculate totals
            $totals = $this->calculateTotals($checkoutData['lines'], $checkoutData);
            
            // Create transaction
            $transaction = $this->transactionService->createTransaction([
                'location_id' => $checkoutData['location_id'],
                'cash_register_id' => $checkoutData['cash_register_id'],
                'type' => TransactionType::SALE,
                'status' => TransactionStatus::COMPLETED,
                'contact_id' => $checkoutData['customer_id'] ?? null,
                'transaction_date' => now(),
                'lines' => $checkoutData['lines'],
                'payments' => $checkoutData['payments'],
                'subtotal' => $totals['subtotal'],
                'tax_amount' => $totals['tax_amount'],
                'discount_amount' => $totals['discount_amount'],
                'total_amount' => $totals['total_amount'],
                'paid_amount' => $totals['paid_amount'],
                'payment_status' => $this->determinePaymentStatus($totals['total_amount'], $totals['paid_amount']),
            ]);
            
            // Update inventory
            $this->updateInventory($transaction);
            
            // Generate receipt
            $receipt = $this->receiptService->generateReceipt($transaction, 'thermal');
            
            // Update cash register balance
            $this->updateCashRegisterBalance($cashRegister, $checkoutData['payments']);
            
            Log::info('POS checkout completed', [
                'transaction_id' => $transaction->id,
                'total' => $totals['total_amount'],
                'customer_id' => $checkoutData['customer_id'] ?? null,
            ]);
            
            return [
                'success' => true,
                'transaction' => $transaction,
                'receipt' => $receipt,
                'change' => max(0, $totals['paid_amount'] - $totals['total_amount']),
            ];
        });
    }

    /**
     * Process quick sale (simplified checkout)
     *
     * @param array $saleData
     * @return Transaction
     */
    public function quickSale(array $saleData): Transaction
    {
        return $this->processCheckout([
            'cash_register_id' => $saleData['cash_register_id'],
            'location_id' => $saleData['location_id'],
            'customer_id' => $saleData['customer_id'] ?? null,
            'lines' => $saleData['lines'],
            'payments' => [[
                'payment_method_id' => $saleData['payment_method_id'] ?? 'cash',
                'amount' => $saleData['total_amount'],
            ]],
        ])['transaction'];
    }

    /**
     * Save transaction as draft
     *
     * @param array $draftData
     * @return Transaction
     */
    public function saveDraft(array $draftData): Transaction
    {
        $totals = $this->calculateTotals($draftData['lines'], $draftData);
        
        return $this->transactionService->createTransaction([
            'location_id' => $draftData['location_id'],
            'cash_register_id' => $draftData['cash_register_id'],
            'type' => TransactionType::SALE,
            'status' => TransactionStatus::DRAFT,
            'contact_id' => $draftData['customer_id'] ?? null,
            'lines' => $draftData['lines'],
            'subtotal' => $totals['subtotal'],
            'tax_amount' => $totals['tax_amount'],
            'discount_amount' => $totals['discount_amount'],
            'total_amount' => $totals['total_amount'],
            'payment_status' => PaymentStatus::DUE,
        ]);
    }

    /**
     * Resume draft transaction
     *
     * @param Transaction $draft
     * @param array $checkoutData
     * @return array
     */
    public function resumeDraft(Transaction $draft, array $checkoutData): array
    {
        if ($draft->status !== TransactionStatus::DRAFT && $draft->status !== TransactionStatus::SUSPENDED) {
            throw new InvalidArgumentException('Transaction is not a draft or suspended');
        }
        
        // If lines are provided, recalculate totals
        if (isset($checkoutData['lines'])) {
            $totals = $this->calculateTotals($checkoutData['lines'], $checkoutData);
            
            // Update draft with new data and totals
            $draft = $this->transactionService->updateTransaction($draft, [
                'status' => TransactionStatus::COMPLETED,
                'lines' => $checkoutData['lines'],
                'subtotal' => $totals['subtotal'],
                'tax_amount' => $totals['tax_amount'],
                'discount_amount' => $totals['discount_amount'],
                'total_amount' => $totals['total_amount'],
            ]);
        } else {
            // Just update status
            $draft->update(['status' => TransactionStatus::COMPLETED]);
        }
        
        // Add payments
        foreach ($checkoutData['payments'] as $payment) {
            $this->transactionService->addPayment($draft, $payment);
        }
        
        // Update inventory
        $this->updateInventory($draft);
        
        // Generate receipt
        $receipt = $this->receiptService->generateReceipt($draft, 'thermal');
        
        return [
            'success' => true,
            'transaction' => $draft->fresh(),
            'receipt' => $receipt,
        ];
    }

    /**
     * Validate checkout data
     *
     * @param array $data
     * @throws InvalidArgumentException
     */
    private function validateCheckoutData(array $data): void
    {
        if (empty($data['cash_register_id'])) {
            throw new InvalidArgumentException('Cash register ID is required');
        }
        
        if (empty($data['location_id'])) {
            throw new InvalidArgumentException('Location ID is required');
        }
        
        if (empty($data['lines']) || !is_array($data['lines'])) {
            throw new InvalidArgumentException('At least one item is required');
        }
        
        if (empty($data['payments']) || !is_array($data['payments'])) {
            throw new InvalidArgumentException('Payment information is required');
        }
    }

    /**
     * Validate cash register is open and ready
     *
     * @param string $registerId
     * @return CashRegister
     * @throws InvalidArgumentException
     */
    private function validateCashRegister(string $registerId): CashRegister
    {
        $register = CashRegister::find($registerId);
        
        if (!$register) {
            throw new InvalidArgumentException('Cash register not found');
        }
        
        if ($register->status !== \Modules\POS\Enums\CashRegisterStatus::OPEN) {
            throw new InvalidArgumentException('Cash register is not open');
        }
        
        return $register;
    }

    /**
     * Validate stock availability for all items
     *
     * @param array $lines
     * @param string $locationId
     * @throws InvalidArgumentException
     */
    private function validateStockAvailability(array $lines, string $locationId): void
    {
        foreach ($lines as $line) {
            $available = $this->stockService->getAvailableQuantity(
                $line['product_id'],
                $locationId,
                $line['variation_id'] ?? null
            );
            
            if ($available < $line['quantity']) {
                throw new InvalidArgumentException(
                    "Insufficient stock for product {$line['product_id']}. Available: {$available}, Required: {$line['quantity']}"
                );
            }
        }
    }

    /**
     * Calculate transaction totals
     *
     * @param array $lines
     * @param array $checkoutData
     * @return array
     */
    private function calculateTotals(array $lines, array $checkoutData): array
    {
        $subtotal = 0;
        $totalTax = 0;
        $totalDiscount = 0;
        
        foreach ($lines as &$line) {
            $lineSubtotal = $line['quantity'] * $line['unit_price'];
            
            // Calculate line discount
            $lineDiscount = $line['discount_amount'] ?? 0;
            if (isset($line['discount_type']) && $line['discount_type'] === 'percentage') {
                $lineDiscount = ($lineSubtotal * $lineDiscount) / 100;
            }
            
            // Calculate line tax
            $taxableAmount = $lineSubtotal - $lineDiscount;
            $lineTax = $line['tax_amount'] ?? 0;
            if (isset($line['tax_rate']) && $line['tax_rate'] > 0) {
                $lineTax = ($taxableAmount * $line['tax_rate']) / 100;
            }
            
            // Calculate line total
            $line['discount_amount'] = $lineDiscount;
            $line['tax_amount'] = $lineTax;
            $line['line_total'] = $taxableAmount + $lineTax;
            
            $subtotal += $lineSubtotal;
            $totalDiscount += $lineDiscount;
            $totalTax += $lineTax;
        }
        
        // Apply transaction-level discount
        $transactionDiscount = $checkoutData['discount_amount'] ?? 0;
        if (isset($checkoutData['discount_type']) && $checkoutData['discount_type'] === 'percentage') {
            $transactionDiscount = ($subtotal * $transactionDiscount) / 100;
        }
        $totalDiscount += $transactionDiscount;
        
        $total = $subtotal - $totalDiscount + $totalTax;
        
        // Calculate total paid
        $paidAmount = 0;
        if (isset($checkoutData['payments'])) {
            foreach ($checkoutData['payments'] as $payment) {
                $paidAmount += $payment['amount'];
            }
        }
        
        return [
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($totalDiscount, 2),
            'tax_amount' => round($totalTax, 2),
            'total_amount' => round($total, 2),
            'paid_amount' => round($paidAmount, 2),
        ];
    }

    /**
     * Update inventory after sale
     *
     * @param Transaction $transaction
     */
    private function updateInventory(Transaction $transaction): void
    {
        foreach ($transaction->lines as $line) {
            $this->stockService->reduceStock(
                $line->product_id,
                $transaction->location_id,
                $line->quantity,
                $line->variation_id,
                "POS Sale: {$transaction->transaction_number}"
            );
        }
    }

    /**
     * Update cash register balance
     *
     * @param CashRegister $register
     * @param array $payments
     */
    private function updateCashRegisterBalance(CashRegister $register, array $payments): void
    {
        foreach ($payments as $payment) {
            if ($payment['payment_method_id'] === 'cash' || 
                (isset($payment['method']) && strtolower($payment['method']) === 'cash')) {
                $this->cashRegisterService->addTransaction($register->id, [
                    'amount' => $payment['amount'],
                    'type' => 'sale',
                    'notes' => 'POS Sale Payment',
                ]);
            }
        }
    }

    /**
     * Determine payment status
     *
     * @param float $totalAmount
     * @param float $paidAmount
     * @return PaymentStatus
     */
    private function determinePaymentStatus(float $totalAmount, float $paidAmount): PaymentStatus
    {
        $epsilon = 0.01;
        
        if ($paidAmount >= ($totalAmount - $epsilon)) {
            return PaymentStatus::PAID;
        } elseif ($paidAmount > $epsilon) {
            return PaymentStatus::PARTIAL;
        }
        return PaymentStatus::DUE;
    }
}
