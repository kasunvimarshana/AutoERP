<?php

declare(strict_types=1);

namespace Modules\Invoice\Services;

use InvalidArgumentException;
use Modules\Invoice\Models\Invoice;

final class InvoiceWhatsAppMessageBuilder
{
    private const MONEY_SCALE = 2;

    public function build(
        Invoice $invoice,
        string $recipientName,
        string $documentNumber,
        string $documentUrl,
    ): string {
        return strtr((string) config('document-sharing.whatsapp.invoice_message'), [
            '{recipient_name}' => $recipientName,
            '{document_number}' => $documentNumber,
            '{invoice_total}' => $this->money($invoice->grand_total, $invoice),
            '{paid_amount}' => $this->money($invoice->paid_total, $invoice),
            '{balance_due}' => $this->money($invoice->balance_due, $invoice),
            '{document_url}' => $documentUrl,
        ]);
    }

    private function money(mixed $value, Invoice $invoice): string
    {
        $prefix = $this->currencyPrefix($invoice);
        $amount = $this->formatDecimal((string) $value);

        return trim($prefix.' '.$amount);
    }

    private function currencyPrefix(Invoice $invoice): string
    {
        $symbol = trim((string) $invoice->currency_symbol_snapshot);
        if ($symbol !== '') {
            return $symbol;
        }

        return trim((string) $invoice->currency_code_snapshot);
    }

    private function formatDecimal(string $value): string
    {
        $decimal = trim($value);
        if (preg_match('/^-?\d+(?:\.\d+)?$/', $decimal) !== 1) {
            throw new InvalidArgumentException('Invoice WhatsApp amounts must be valid decimal values.');
        }

        $increment = '0.'.str_repeat('0', self::MONEY_SCALE).'5';
        $rounded = bcadd(
            $decimal,
            str_starts_with($decimal, '-') ? '-'.$increment : $increment,
            self::MONEY_SCALE,
        );
        if (bccomp($rounded, '0', self::MONEY_SCALE) === 0) {
            $rounded = '0.'.str_repeat('0', self::MONEY_SCALE);
        }

        $negative = str_starts_with($rounded, '-');
        $absolute = ltrim($rounded, '-');
        [$whole, $fraction] = array_pad(explode('.', $absolute, 2), 2, '');
        $whole = preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', $whole) ?? $whole;

        return ($negative ? '-' : '').$whole.'.'.str_pad($fraction, self::MONEY_SCALE, '0');
    }
}
