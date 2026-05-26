<?php

declare(strict_types=1);

namespace Modules\Invoice\Application\UseCases\InvoiceEngines;

use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Invoice\Application\Contracts\UseCases\InvoiceEngines\RecalculateInvoiceTotalsServiceInterface;
use Modules\Invoice\Application\Repositories\InvoiceLineRepositoryInterface;
use Modules\Invoice\Application\Repositories\InvoiceRepositoryInterface;
use Modules\Invoice\Domain\Constants\InvoiceErrorCode;
use Throwable;

final class RecalculateInvoiceTotalsService implements RecalculateInvoiceTotalsServiceInterface
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoices,
        private readonly InvoiceLineRepositoryInterface $invoiceLines,
    ) {
    }

    public function execute(int|string $invoiceId, array $payload): Result
    {
        try {
            $invoice = $this->invoices->findById($invoiceId);
            if (! $invoice instanceof DataRecord) {
                return Result::failure(new Error(InvoiceErrorCode::NOT_FOUND, 'Invoice not found.'));
            }

            $lineTaxRates = $this->extractLineTaxRates($payload['line_tax_rates'] ?? []);
            $headerTaxRatePercent = isset($payload['header_tax_rate_percent'])
                ? $this->normalizePercent($payload['header_tax_rate_percent'])
                : null;

            $result = $this->invoices->transaction(function () use (
                $invoice,
                $lineTaxRates,
                $headerTaxRatePercent,
            ): array {
                $lines = $this->invoiceLines->list(['invoice_id' => (int) $invoice->id()]);

                $subtotal = 0.0;
                $lineTaxTotal = 0.0;
                $lineDiscountTotal = 0.0;

                foreach ($lines as $line) {
                    if (! $line instanceof DataRecord) {
                        continue;
                    }

                    $quantity = $this->toDecimal($line->get('quantity', 0));
                    $unitPrice = $this->toDecimal($line->get('unit_price', 0));
                    $gross = $this->round4($quantity * $unitPrice);

                    $discountType = strtolower(trim((string) $line->get('discount_type', '')));
                    $discountValue = $this->toDecimal($line->get('discount_value', 0));
                    $discountAmount = $this->calculateDiscount($gross, $discountType, $discountValue);

                    $lineTotal = $this->round4($gross - $discountAmount);

                    $lineId = (int) $line->id();
                    if (array_key_exists($lineId, $lineTaxRates)) {
                        $taxAmount = $this->round4($lineTotal * ($lineTaxRates[$lineId] / 100));
                    } else {
                        $taxAmount = $this->toDecimal($line->get('tax_amount', 0));
                    }

                    $lineTotalWithTax = $this->round4($lineTotal + $taxAmount);

                    $this->invoiceLines->update($lineId, [
                        'gross_amount' => $gross,
                        'discount_amount' => $discountAmount,
                        'line_total' => $lineTotal,
                        'tax_amount' => $taxAmount,
                        'line_total_with_tax' => $lineTotalWithTax,
                        'row_version' => ((int) $line->get('row_version', 1)) + 1,
                    ]);

                    $subtotal += $gross;
                    $lineTaxTotal += $taxAmount;
                    $lineDiscountTotal += $discountAmount;
                }

                $subtotal = $this->round4($subtotal);
                $lineTaxTotal = $this->round4($lineTaxTotal);
                $lineDiscountTotal = $this->round4($lineDiscountTotal);

                $headerDiscountType = strtolower(trim((string) $invoice->get('header_discount_type', '')));
                $headerDiscountValue = $this->toDecimal($invoice->get('header_discount_value', 0));
                $headerDiscountAmount = $this->calculateDiscount(
                    $subtotal,
                    $headerDiscountType,
                    $headerDiscountValue,
                );

                if ($headerTaxRatePercent === null) {
                    $headerTaxAmount = $this->toDecimal($invoice->get('header_tax_amount', 0));
                } else {
                    $headerTaxAmount = $this->round4(
                        max(0.0, $subtotal - $headerDiscountAmount) * ($headerTaxRatePercent / 100),
                    );
                }

                $discountTotal = $this->round4($lineDiscountTotal + $headerDiscountAmount);
                $taxTotal = $this->round4($lineTaxTotal + $headerTaxAmount);
                $debitNoteTotal = $this->toDecimal($invoice->get('debit_note_total', 0));
                $creditNoteTotal = $this->toDecimal($invoice->get('credit_note_total', 0));
                $paidAmount = $this->toDecimal($invoice->get('paid_amount', 0));

                $grandTotal = $this->round4(
                    $subtotal
                    - $discountTotal
                    + $taxTotal
                    + $debitNoteTotal
                    - $creditNoteTotal,
                );

                $balance = $this->round4($grandTotal - $paidAmount);

                $updatedInvoice = $this->invoices->update((int) $invoice->id(), [
                    'subtotal' => $subtotal,
                    'line_tax_total' => $lineTaxTotal,
                    'line_discount_total' => $lineDiscountTotal,
                    'header_discount_amount' => $headerDiscountAmount,
                    'header_tax_amount' => $headerTaxAmount,
                    'discount_total' => $discountTotal,
                    'tax_total' => $taxTotal,
                    'grand_total' => $grandTotal,
                    'balance' => $balance,
                    'row_version' => ((int) $invoice->get('row_version', 1)) + 1,
                ]);

                return [
                    'invoice' => $updatedInvoice->toArray(),
                    'line_count' => count($lines),
                ];
            });

            return Result::success($result);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InvoiceErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    /**
     * @param mixed $value
     */
    private function toDecimal(mixed $value): float
    {
        return (float) $value;
    }

    private function round4(float $value): float
    {
        return round($value, 4);
    }

    private function normalizePercent(mixed $value): float
    {
        $percent = $this->toDecimal($value);

        return min(100.0, max(0.0, $percent));
    }

    private function calculateDiscount(float $base, string $type, float $value): float
    {
        if ($base <= 0) {
            return 0.0;
        }

        if ($type === 'percentage') {
            return $this->round4($base * ($this->normalizePercent($value) / 100));
        }

        if ($type === 'fixed') {
            return $this->round4(min($base, max(0.0, $value)));
        }

        return 0.0;
    }

    /**
     * @param mixed $taxRates
     * @return array<int, float>
     */
    private function extractLineTaxRates(mixed $taxRates): array
    {
        if (! is_array($taxRates)) {
            return [];
        }

        $resolved = [];
        foreach ($taxRates as $key => $value) {
            if (! is_scalar($key) || ! is_numeric((string) $key)) {
                continue;
            }

            $resolved[(int) $key] = $this->normalizePercent($value);
        }

        return $resolved;
    }
}
