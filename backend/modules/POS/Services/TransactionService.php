<?php

declare(strict_types=1);

namespace Modules\POS\Services;

use Modules\POS\Models\Transaction;
use Modules\POS\Models\TransactionLine;
use Modules\POS\Models\TransactionPayment;
use Modules\POS\Enums\TransactionStatus;
use Modules\POS\Enums\PaymentStatus;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function __construct(
        private ReferenceNumberService $referenceNumberService
    ) {}

    public function createTransaction(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            // Generate transaction number
            $transactionNumber = $this->referenceNumberService->generate('transaction');
            
            $transaction = Transaction::create([
                'location_id' => $data['location_id'],
                'type' => $data['type'],
                'status' => $data['status'] ?? TransactionStatus::DRAFT,
                'transaction_number' => $transactionNumber,
                'contact_id' => $data['contact_id'] ?? null,
                'cash_register_id' => $data['cash_register_id'] ?? null,
                'transaction_date' => $data['transaction_date'] ?? now(),
                'invoice_scheme_id' => $data['invoice_scheme_id'] ?? null,
                'invoice_number' => $data['invoice_number'] ?? null,
                'subtotal' => $data['subtotal'] ?? 0,
                'tax_amount' => $data['tax_amount'] ?? 0,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'discount_type' => $data['discount_type'] ?? null,
                'shipping_charges' => $data['shipping_charges'] ?? 0,
                'total_amount' => $data['total_amount'],
                'payment_status' => $data['payment_status'] ?? PaymentStatus::DUE,
                'paid_amount' => $data['paid_amount'] ?? 0,
                'notes' => $data['notes'] ?? null,
                'additional_data' => $data['additional_data'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Create transaction lines
            if (isset($data['lines'])) {
                foreach ($data['lines'] as $line) {
                    $this->createTransactionLine($transaction, $line);
                }
            }

            // Create payments
            if (isset($data['payments'])) {
                foreach ($data['payments'] as $payment) {
                    $this->addPayment($transaction, $payment);
                }
            }

            return $transaction->fresh(['lines', 'payments']);
        });
    }

    public function updateTransaction(Transaction $transaction, array $data): Transaction
    {
        return DB::transaction(function () use ($transaction, $data) {
            $transaction->update([
                'status' => $data['status'] ?? $transaction->status,
                'contact_id' => $data['contact_id'] ?? $transaction->contact_id,
                'subtotal' => $data['subtotal'] ?? $transaction->subtotal,
                'tax_amount' => $data['tax_amount'] ?? $transaction->tax_amount,
                'discount_amount' => $data['discount_amount'] ?? $transaction->discount_amount,
                'discount_type' => $data['discount_type'] ?? $transaction->discount_type,
                'shipping_charges' => $data['shipping_charges'] ?? $transaction->shipping_charges,
                'total_amount' => $data['total_amount'] ?? $transaction->total_amount,
                'notes' => $data['notes'] ?? $transaction->notes,
                'updated_by' => auth()->id(),
            ]);

            // Update lines if provided
            if (isset($data['lines'])) {
                $transaction->lines()->delete();
                foreach ($data['lines'] as $line) {
                    $this->createTransactionLine($transaction, $line);
                }
            }

            return $transaction->fresh(['lines', 'payments']);
        });
    }

    private function createTransactionLine(Transaction $transaction, array $lineData): TransactionLine
    {
        return TransactionLine::create([
            'transaction_id' => $transaction->id,
            'product_id' => $lineData['product_id'],
            'variation_id' => $lineData['variation_id'] ?? null,
            'quantity' => $lineData['quantity'],
            'unit' => $lineData['unit'] ?? null,
            'unit_price' => $lineData['unit_price'],
            'discount_amount' => $lineData['discount_amount'] ?? 0,
            'discount_type' => $lineData['discount_type'] ?? null,
            'tax_rate_id' => $lineData['tax_rate_id'] ?? null,
            'tax_amount' => $lineData['tax_amount'] ?? 0,
            'line_total' => $lineData['line_total'],
            'lot_number' => $lineData['lot_number'] ?? null,
            'expiry_date' => $lineData['expiry_date'] ?? null,
            'notes' => $lineData['notes'] ?? null,
        ]);
    }

    public function addPayment(Transaction $transaction, array $paymentData): TransactionPayment
    {
        $payment = TransactionPayment::create([
            'transaction_id' => $transaction->id,
            'payment_method_id' => $paymentData['payment_method_id'],
            'amount' => $paymentData['amount'],
            'payment_date' => $paymentData['payment_date'] ?? now(),
            'payment_reference' => $paymentData['payment_reference'] ?? null,
            'notes' => $paymentData['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        // Update transaction paid amount and payment status
        $totalPaid = $transaction->payments()->sum('amount');
        $transaction->update([
            'paid_amount' => $totalPaid,
            'payment_status' => $this->determinePaymentStatus($transaction->total_amount, $totalPaid),
        ]);

        return $payment;
    }

    private function determinePaymentStatus(float $totalAmount, float $paidAmount): PaymentStatus
    {
        // Use epsilon comparison for floating-point monetary values
        $epsilon = 0.01; // 1 cent tolerance
        
        if ($paidAmount >= ($totalAmount - $epsilon)) {
            return PaymentStatus::PAID;
        } elseif ($paidAmount > $epsilon) {
            return PaymentStatus::PARTIAL;
        }
        return PaymentStatus::DUE;
    }

    public function completeTransaction(Transaction $transaction): Transaction
    {
        $transaction->update([
            'status' => TransactionStatus::COMPLETED,
            'updated_by' => auth()->id(),
        ]);

        return $transaction;
    }

    public function cancelTransaction(Transaction $transaction): Transaction
    {
        $transaction->update([
            'status' => TransactionStatus::CANCELLED,
            'updated_by' => auth()->id(),
        ]);

        return $transaction;
    }
}
