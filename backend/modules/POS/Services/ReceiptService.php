<?php

declare(strict_types=1);

namespace Modules\POS\Services;

use Modules\POS\Models\Transaction;
use Modules\POS\Models\InvoiceLayout;
use Modules\POS\Repositories\ReceiptRepository;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;

/**
 * Receipt Service
 * 
 * Handles receipt generation, formatting, and delivery for POS transactions.
 * Supports thermal and standard receipts with customizable layouts.
 */
class ReceiptService
{
    public function __construct(
        private ReceiptRepository $receiptRepository
    ) {}

    /**
     * Generate receipt for a transaction
     *
     * @param Transaction $transaction
     * @param string $format 'thermal'|'standard'|'a4'
     * @return array Receipt data with formatted content
     */
    public function generateReceipt(Transaction $transaction, string $format = 'thermal'): array
    {
        $invoiceLayout = $this->getInvoiceLayout($transaction);
        
        $receiptData = $this->prepareReceiptData($transaction, $invoiceLayout);
        
        $formattedContent = match($format) {
            'thermal' => $this->formatThermalReceipt($receiptData),
            'a4' => $this->formatA4Receipt($receiptData),
            default => $this->formatStandardReceipt($receiptData)
        };

        // Store receipt record
        $receipt = $this->receiptRepository->create([
            'transaction_id' => $transaction->id,
            'format' => $format,
            'content' => $formattedContent,
            'printed_at' => null, // Will be set when actually printed
        ]);

        Log::info('Receipt generated', [
            'transaction_id' => $transaction->id,
            'receipt_id' => $receipt->id,
            'format' => $format
        ]);

        return [
            'receipt_id' => $receipt->id,
            'transaction_id' => $transaction->id,
            'format' => $format,
            'content' => $formattedContent,
            'raw_data' => $receiptData,
        ];
    }

    /**
     * Email receipt to customer
     *
     * @param Transaction $transaction
     * @param string $email
     * @return bool
     */
    public function emailReceipt(Transaction $transaction, string $email): bool
    {
        try {
            $receiptData = $this->generateReceipt($transaction, 'standard');
            
            // TODO: Implement email notification using Laravel Mail
            // Mail::to($email)->send(new ReceiptMail($receiptData));
            
            Log::info('Receipt emailed', [
                'transaction_id' => $transaction->id,
                'email' => $email
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to email receipt', [
                'transaction_id' => $transaction->id,
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Prepare receipt data from transaction
     *
     * @param Transaction $transaction
     * @param InvoiceLayout|null $layout
     * @return array
     */
    private function prepareReceiptData(Transaction $transaction, ?InvoiceLayout $layout): array
    {
        $transaction->load(['location', 'lines.product', 'payments.paymentMethod', 'cashRegister']);
        
        return [
            'business' => [
                'name' => $transaction->location->name ?? 'Business Name',
                'address' => $transaction->location->address ?? '',
                'phone' => $transaction->location->phone ?? '',
                'tax_number' => $transaction->location->tax_number ?? '',
                'logo_url' => $layout?->logo_url ?? null,
            ],
            'transaction' => [
                'number' => $transaction->transaction_number,
                'invoice_number' => $transaction->invoice_number,
                'date' => $transaction->transaction_date->format('Y-m-d H:i:s'),
                'cashier' => $transaction->creator?->name ?? 'System',
                'register' => $transaction->cashRegister?->name ?? 'N/A',
            ],
            'customer' => [
                'name' => $transaction->contact?->name ?? 'Walk-in Customer',
                'phone' => $transaction->contact?->phone ?? null,
                'email' => $transaction->contact?->email ?? null,
            ],
            'items' => $transaction->lines->map(fn($line) => [
                'name' => $line->product->name ?? 'Unknown Product',
                'quantity' => $line->quantity,
                'unit_price' => number_format($line->unit_price, 2),
                'discount' => number_format($line->discount_amount, 2),
                'tax' => number_format($line->tax_amount, 2),
                'total' => number_format($line->line_total, 2),
            ])->toArray(),
            'totals' => [
                'subtotal' => number_format($transaction->subtotal, 2),
                'discount' => number_format($transaction->discount_amount, 2),
                'tax' => number_format($transaction->tax_amount, 2),
                'shipping' => number_format($transaction->shipping_charges, 2),
                'total' => number_format($transaction->total_amount, 2),
                'paid' => number_format($transaction->paid_amount, 2),
                'change' => number_format(
                    max(0, $transaction->paid_amount - $transaction->total_amount), 
                    2
                ),
            ],
            'payments' => $transaction->payments->map(fn($payment) => [
                'method' => $payment->paymentMethod->name ?? 'Cash',
                'amount' => number_format($payment->amount, 2),
                'reference' => $payment->payment_reference,
            ])->toArray(),
            'footer' => $layout?->footer_text ?? 'Thank you for your business!',
        ];
    }

    /**
     * Format receipt for thermal printer (58mm or 80mm)
     *
     * @param array $data
     * @return string
     */
    private function formatThermalReceipt(array $data): string
    {
        $output = [];
        $width = 48; // Characters width for 80mm printer
        
        // Header
        $output[] = str_pad('', $width, '=');
        $output[] = $this->centerText($data['business']['name'], $width);
        if ($data['business']['address']) {
            $output[] = $this->centerText($data['business']['address'], $width);
        }
        if ($data['business']['phone']) {
            $output[] = $this->centerText('Tel: ' . $data['business']['phone'], $width);
        }
        if ($data['business']['tax_number']) {
            $output[] = $this->centerText('Tax #: ' . $data['business']['tax_number'], $width);
        }
        $output[] = str_pad('', $width, '=');
        
        // Transaction info
        $output[] = 'Invoice: ' . $data['transaction']['invoice_number'];
        $output[] = 'Date: ' . $data['transaction']['date'];
        $output[] = 'Cashier: ' . $data['transaction']['cashier'];
        $output[] = 'Customer: ' . $data['customer']['name'];
        $output[] = str_pad('', $width, '-');
        
        // Items
        $output[] = sprintf('%-25s %6s %10s', 'Item', 'Qty', 'Amount');
        $output[] = str_pad('', $width, '-');
        
        foreach ($data['items'] as $item) {
            $output[] = sprintf('%-25s %6s %10s',
                substr($item['name'], 0, 25),
                $item['quantity'],
                $item['total']
            );
        }
        
        $output[] = str_pad('', $width, '-');
        
        // Totals
        $output[] = sprintf('%-32s %10s', 'Subtotal:', $data['totals']['subtotal']);
        if ((float)$data['totals']['discount'] > 0) {
            $output[] = sprintf('%-32s %10s', 'Discount:', '-' . $data['totals']['discount']);
        }
        if ((float)$data['totals']['tax'] > 0) {
            $output[] = sprintf('%-32s %10s', 'Tax:', $data['totals']['tax']);
        }
        if ((float)$data['totals']['shipping'] > 0) {
            $output[] = sprintf('%-32s %10s', 'Shipping:', $data['totals']['shipping']);
        }
        $output[] = str_pad('', $width, '=');
        $output[] = sprintf('%-32s %10s', 'TOTAL:', $data['totals']['total']);
        $output[] = sprintf('%-32s %10s', 'Paid:', $data['totals']['paid']);
        $output[] = sprintf('%-32s %10s', 'Change:', $data['totals']['change']);
        $output[] = str_pad('', $width, '=');
        
        // Payment methods
        $output[] = 'Payment Methods:';
        foreach ($data['payments'] as $payment) {
            $output[] = sprintf('  %-28s %10s', $payment['method'], $payment['amount']);
        }
        
        $output[] = str_pad('', $width, '-');
        
        // Footer
        $output[] = $this->centerText($data['footer'], $width);
        $output[] = str_pad('', $width, '=');
        $output[] = ''; // Blank line for paper cut
        
        return implode("\n", $output);
    }

    /**
     * Format receipt for standard printing
     *
     * @param array $data
     * @return string
     */
    private function formatStandardReceipt(array $data): string
    {
        // Similar to thermal but with HTML formatting for web/email
        return View::make('pos::receipts.standard', compact('data'))->render();
    }

    /**
     * Format receipt for A4 paper
     *
     * @param array $data
     * @return string
     */
    private function formatA4Receipt(array $data): string
    {
        return View::make('pos::receipts.a4', compact('data'))->render();
    }

    /**
     * Center text within given width
     *
     * @param string $text
     * @param int $width
     * @return string
     */
    private function centerText(string $text, int $width): string
    {
        $textLength = strlen($text);
        if ($textLength >= $width) {
            return substr($text, 0, $width);
        }
        
        $padding = ($width - $textLength) / 2;
        return str_pad($text, $width, ' ', STR_PAD_BOTH);
    }

    /**
     * Get invoice layout for transaction
     *
     * @param Transaction $transaction
     * @return InvoiceLayout|null
     */
    private function getInvoiceLayout(Transaction $transaction): ?InvoiceLayout
    {
        if ($transaction->invoice_scheme_id) {
            return InvoiceLayout::where('invoice_scheme_id', $transaction->invoice_scheme_id)
                ->first();
        }
        
        // Get default layout for location
        return InvoiceLayout::where('location_id', $transaction->location_id)
            ->where('is_default', true)
            ->first();
    }

    /**
     * Print receipt (send to printer)
     *
     * @param Transaction $transaction
     * @param string $printerName
     * @return bool
     */
    public function printReceipt(Transaction $transaction, string $printerName = 'default'): bool
    {
        try {
            $receiptData = $this->generateReceipt($transaction, 'thermal');
            
            // TODO: Implement printer integration
            // This would connect to actual thermal printer via ESC/POS commands
            // or use network printer API
            
            // Mark receipt as printed
            $this->receiptRepository->markAsPrinted($receiptData['receipt_id']);
            
            Log::info('Receipt printed', [
                'transaction_id' => $transaction->id,
                'printer' => $printerName
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to print receipt', [
                'transaction_id' => $transaction->id,
                'printer' => $printerName,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
