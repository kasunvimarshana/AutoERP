<?php

declare(strict_types=1);

namespace App\Modules\POS\Services;

use App\Core\Services\BaseService;
use App\Core\Exceptions\ServiceException;
use App\Modules\POS\Repositories\POSSessionRepositoryInterface;
use App\Modules\POS\Repositories\POSTransactionRepositoryInterface;
use App\Modules\POS\Models\POSSession;
use App\Modules\POS\Models\POSTransaction;
use App\Modules\POS\Models\POSTransactionItem;
use App\Modules\POS\Models\POSReceipt;
use App\Modules\Payment\Services\PaymentService;
use App\Modules\Inventory\Services\StockLedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class POSService extends BaseService
{
    protected POSSessionRepositoryInterface $sessionRepository;
    protected PaymentService $paymentService;
    protected ?StockLedgerService $stockLedgerService;

    public function __construct(
        POSTransactionRepositoryInterface $repository,
        POSSessionRepositoryInterface $sessionRepository,
        PaymentService $paymentService,
        ?StockLedgerService $stockLedgerService = null
    ) {
        parent::__construct($repository);
        $this->sessionRepository = $sessionRepository;
        $this->paymentService = $paymentService;
        $this->stockLedgerService = $stockLedgerService;
    }

    /**
     * Open a new POS session
     */
    public function openSession(array $data): POSSession
    {
        DB::beginTransaction();
        try {
            // Check if cashier already has an open session
            $existingSession = $this->sessionRepository->getCurrentOpenSession(
                $data['cashier_id'],
                $data['terminal_id'] ?? null
            );

            if ($existingSession) {
                throw new ServiceException('Cashier already has an open session');
            }

            // Generate session number
            $data['session_number'] = $this->generateSessionNumber();
            $data['opened_at'] = now();
            $data['status'] = 'open';
            $data['opening_cash'] = $data['opening_cash'] ?? 0;
            $data['transaction_count'] = 0;
            $data['total_sales'] = 0;
            $data['total_returns'] = 0;
            $data['total_cash_sales'] = 0;
            $data['total_card_sales'] = 0;
            $data['total_other_sales'] = 0;

            $session = $this->sessionRepository->create($data);

            DB::commit();
            return $session->load(['cashier']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to open session: ' . $e->getMessage());
        }
    }

    /**
     * Close a POS session
     */
    public function closeSession(int $sessionId, array $data): POSSession
    {
        DB::beginTransaction();
        try {
            $session = $this->sessionRepository->findById($sessionId);
            if (!$session) {
                throw new ServiceException('Session not found');
            }

            if ($session->status === 'closed') {
                throw new ServiceException('Session is already closed');
            }

            // Get session totals
            $totals = $this->repository->getTotalBySession($sessionId);

            $updateData = [
                'closed_at' => now(),
                'closing_cash' => $data['closing_cash'],
                'expected_cash' => bcadd((string)$session->opening_cash, $totals['total_cash'], 2),
                'cash_difference' => bcsub((string)$data['closing_cash'], bcadd((string)$session->opening_cash, $totals['total_cash'], 2), 2),
                'total_sales' => $totals['total_sales'],
                'total_cash_sales' => $totals['total_cash'],
                'total_card_sales' => $totals['total_card'],
                'transaction_count' => $totals['transaction_count'],
                'status' => 'closed',
                'notes' => $data['notes'] ?? null,
            ];

            $session = $this->sessionRepository->update($sessionId, $updateData);

            DB::commit();
            return $session->load(['cashier', 'transactions']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to close session: ' . $e->getMessage());
        }
    }

    /**
     * Create a POS transaction
     */
    public function createTransaction(array $data): POSTransaction
    {
        DB::beginTransaction();
        try {
            // Validate session is open
            $session = $this->sessionRepository->findById($data['session_id']);
            if (!$session || $session->status !== 'open') {
                throw new ServiceException('Session is not open');
            }

            // Generate transaction number
            $data['transaction_number'] = $this->generateTransactionNumber();
            $data['transaction_date'] = now();
            $data['status'] = 'pending';
            $data['payment_status'] = 'pending';

            // Calculate totals
            $totals = $this->calculateTransactionTotals($data['items']);
            $data['subtotal'] = $totals['subtotal'];
            $data['discount_amount'] = $totals['discount_amount'];
            $data['tax_amount'] = $totals['tax_amount'];
            $data['total_amount'] = $totals['total_amount'];
            $data['paid_amount'] = $data['paid_amount'] ?? $totals['total_amount'];
            $data['change_amount'] = bcsub((string)$data['paid_amount'], $totals['total_amount'], 2);

            $transaction = $this->repository->create($data);

            // Create transaction items and update inventory
            foreach ($data['items'] as $item) {
                $itemData = [
                    'transaction_id' => $transaction->id,
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'product_name' => $item['product_name'],
                    'product_sku' => $item['product_sku'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_type' => $item['discount_type'] ?? 'none',
                    'discount_value' => $item['discount_value'] ?? 0,
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'tax_amount' => $item['tax_amount'] ?? 0,
                    'subtotal' => $item['subtotal'],
                    'total' => $item['total'],
                    'cost_price' => $item['cost_price'] ?? 0,
                    'profit' => bcsub((string)$item['total'], bcmul((string)$item['cost_price'], (string)$item['quantity'], 2), 2),
                    'batch_number' => $item['batch_number'] ?? null,
                    'serial_number' => $item['serial_number'] ?? null,
                ];

                POSTransactionItem::create($itemData);

                // Update inventory if StockLedgerService is available
                if ($this->stockLedgerService) {
                    $this->stockLedgerService->recordStockOut([
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'reference_type' => 'pos_transaction',
                        'reference_id' => $transaction->id,
                        'location_id' => $data['location_id'] ?? null,
                        'batch_number' => $item['batch_number'] ?? null,
                        'serial_number' => $item['serial_number'] ?? null,
                        'notes' => "POS Sale - {$transaction->transaction_number}",
                    ]);
                }
            }

            // Auto-complete if paid
            if ($data['paid_amount'] >= $totals['total_amount']) {
                $transaction = $this->completeTransaction($transaction->id);
            }

            DB::commit();
            return $transaction->load(['items.product', 'customer', 'session']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to create transaction: ' . $e->getMessage());
        }
    }

    /**
     * Complete a transaction
     */
    public function completeTransaction(int $transactionId): POSTransaction
    {
        DB::beginTransaction();
        try {
            $transaction = $this->repository->findById($transactionId);
            if (!$transaction) {
                throw new ServiceException('Transaction not found');
            }

            if ($transaction->status === 'completed') {
                throw new ServiceException('Transaction is already completed');
            }

            // Create payment record
            $paymentData = [
                'payment_type' => 'received',
                'payment_date' => now()->toDateString(),
                'entity_type' => $transaction->customer_id ? 'App\Modules\CRM\Models\Customer' : null,
                'entity_id' => $transaction->customer_id,
                'payment_method_id' => $transaction->payment_method_id,
                'amount' => $transaction->total_amount,
                'reference_number' => $transaction->transaction_number,
                'status' => 'completed',
                'notes' => "POS Transaction - {$transaction->transaction_number}",
            ];

            $payment = $this->paymentService->create($paymentData);

            // Update transaction
            $transaction = $this->repository->update($transactionId, [
                'status' => 'completed',
                'payment_status' => 'paid',
            ]);

            DB::commit();
            return $transaction->load(['items.product', 'customer', 'session', 'payment']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to complete transaction: ' . $e->getMessage());
        }
    }

    /**
     * Generate receipt
     */
    public function generateReceipt(int $transactionId, string $format = 'pdf'): POSReceipt
    {
        DB::beginTransaction();
        try {
            $transaction = $this->repository->findById($transactionId);
            if (!$transaction) {
                throw new ServiceException('Transaction not found');
            }

            $transaction->load(['items.product', 'customer', 'session.cashier']);

            $receiptData = [
                'transaction_id' => $transaction->id,
                'receipt_number' => $this->generateReceiptNumber(),
                'receipt_date' => now(),
                'receipt_type' => 'sale',
                'format' => $format,
                'content' => $this->formatReceiptContent($transaction),
            ];

            $receipt = POSReceipt::create($receiptData);

            DB::commit();
            return $receipt->load(['transaction']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new ServiceException('Failed to generate receipt: ' . $e->getMessage());
        }
    }

    /**
     * Calculate transaction totals
     */
    protected function calculateTransactionTotals(array $items): array
    {
        $subtotal = '0.00';
        $discountAmount = '0.00';
        $taxAmount = '0.00';

        foreach ($items as &$item) {
            $itemSubtotal = bcmul((string)$item['quantity'], (string)$item['unit_price'], 2);
            
            // Calculate discount
            $itemDiscount = '0.00';
            if (($item['discount_type'] ?? 'none') === 'percentage') {
                $itemDiscount = bcmul($itemSubtotal, bcdiv((string)$item['discount_value'], '100', 4), 2);
            } elseif (($item['discount_type'] ?? 'none') === 'flat') {
                $itemDiscount = (string)$item['discount_value'];
            }
            
            $itemSubtotalAfterDiscount = bcsub($itemSubtotal, $itemDiscount, 2);
            
            // Calculate tax
            $itemTax = bcmul($itemSubtotalAfterDiscount, bcdiv((string)($item['tax_rate'] ?? 0), '100', 4), 2);
            
            $itemTotal = bcadd($itemSubtotalAfterDiscount, $itemTax, 2);
            
            $item['subtotal'] = $itemSubtotal;
            $item['discount_amount'] = $itemDiscount;
            $item['tax_amount'] = $itemTax;
            $item['total'] = $itemTotal;
            
            $subtotal = bcadd($subtotal, $itemSubtotal, 2);
            $discountAmount = bcadd($discountAmount, $itemDiscount, 2);
            $taxAmount = bcadd($taxAmount, $itemTax, 2);
        }

        $totalAmount = bcsub(bcadd($subtotal, $taxAmount, 2), $discountAmount, 2);

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
        ];
    }

    /**
     * Format receipt content
     */
    protected function formatReceiptContent(POSTransaction $transaction): string
    {
        // This is a simplified version - implement proper receipt formatting
        $content = "RECEIPT\n";
        $content .= "Transaction: {$transaction->transaction_number}\n";
        $content .= "Date: {$transaction->transaction_date->format('Y-m-d H:i:s')}\n";
        $content .= "Cashier: {$transaction->session->cashier->name}\n\n";
        $content .= "ITEMS:\n";

        foreach ($transaction->items as $item) {
            $content .= "{$item->product_name} x {$item->quantity} @ {$item->unit_price} = {$item->total}\n";
        }

        $content .= "\nSubtotal: {$transaction->subtotal}\n";
        $content .= "Tax: {$transaction->tax_amount}\n";
        $content .= "Discount: {$transaction->discount_amount}\n";
        $content .= "TOTAL: {$transaction->total_amount}\n";
        $content .= "Paid: {$transaction->paid_amount}\n";
        $content .= "Change: {$transaction->change_amount}\n";

        return $content;
    }

    protected function generateSessionNumber(): string
    {
        return 'SES-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
    }

    protected function generateTransactionNumber(): string
    {
        return 'POS-' . now()->format('Ymd') . '-' . strtoupper(Str::random(8));
    }

    protected function generateReceiptNumber(): string
    {
        return 'RCP-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
    }
}
