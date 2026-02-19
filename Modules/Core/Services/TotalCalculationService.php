<?php

declare(strict_types=1);

namespace Modules\Core\Services;

use Modules\Core\Helpers\MathHelper;

/**
 * Centralized total calculation service for financial documents.
 *
 * Provides consistent calculation logic for orders, invoices, bills, and quotations
 * with BCMath precision for all financial operations.
 */
class TotalCalculationService
{
    /**
     * Calculate totals for line items with taxes and discounts.
     *
     * @param  array  $items  Array of line items with quantity, unit_price, discount_amount, tax_rate
     * @param  array  $documentData  Document-level data (may include shipping, additional_tax, etc.)
     * @return array Calculated totals [subtotal, tax_amount, total_amount]
     */
    public function calculateLineTotals(array $items, array $documentData = []): array
    {
        $subtotal = '0';
        $taxAmount = '0';

        foreach ($items as $item) {
            // Calculate line total
            $lineTotal = MathHelper::multiply(
                (string) ($item['quantity'] ?? '1'),
                (string) ($item['unit_price'] ?? '0')
            );

            // Apply line-level discount
            $discount = (string) ($item['discount_amount'] ?? '0');
            $lineTotal = MathHelper::subtract($lineTotal, $discount);

            // Calculate line-level tax
            if (isset($item['tax_rate']) && (float) $item['tax_rate'] > 0) {
                $lineTax = MathHelper::percentage($lineTotal, (string) $item['tax_rate']);
                $taxAmount = MathHelper::add($taxAmount, $lineTax);
            }

            $subtotal = MathHelper::add($subtotal, $lineTotal);
        }

        // Apply document-level adjustments
        $adjustments = $this->calculateDocumentAdjustments($subtotal, $documentData);

        $subtotal = MathHelper::add($subtotal, $adjustments['shipping_amount'] ?? '0');
        $taxAmount = MathHelper::add($taxAmount, $adjustments['additional_tax'] ?? '0');
        $subtotal = MathHelper::subtract($subtotal, $adjustments['discount_amount'] ?? '0');

        // Calculate final total
        $totalAmount = MathHelper::add($subtotal, $taxAmount);

        return [
            'subtotal' => MathHelper::format($subtotal, 2, '.', ''),
            'tax_amount' => MathHelper::format($taxAmount, 2, '.', ''),
            'total_amount' => MathHelper::format($totalAmount, 2, '.', ''),
            'discount_amount' => MathHelper::format($adjustments['discount_amount'] ?? '0', 2, '.', ''),
            'shipping_amount' => MathHelper::format($adjustments['shipping_amount'] ?? '0', 2, '.', ''),
        ];
    }

    /**
     * Calculate document-level adjustments (shipping, discounts, additional taxes).
     *
     * @param  string  $subtotal  Current subtotal
     * @param  array  $documentData  Document data with optional shipping_amount, discount_amount, etc.
     * @return array Calculated adjustments
     */
    private function calculateDocumentAdjustments(string $subtotal, array $documentData): array
    {
        $adjustments = [
            'shipping_amount' => '0',
            'discount_amount' => '0',
            'additional_tax' => '0',
        ];

        // Shipping
        if (isset($documentData['shipping_amount'])) {
            $adjustments['shipping_amount'] = (string) $documentData['shipping_amount'];
        }

        // Document-level discount
        if (isset($documentData['discount_amount'])) {
            $adjustments['discount_amount'] = (string) $documentData['discount_amount'];
        } elseif (isset($documentData['discount_percent']) && (float) $documentData['discount_percent'] > 0) {
            $adjustments['discount_amount'] = MathHelper::percentage(
                $subtotal,
                (string) $documentData['discount_percent']
            );
        }

        // Additional tax
        if (isset($documentData['additional_tax'])) {
            $adjustments['additional_tax'] = (string) $documentData['additional_tax'];
        }

        return $adjustments;
    }

    /**
     * Calculate payment balance.
     *
     * @param  string  $totalAmount  Total amount due
     * @param  string  $paidAmount  Amount already paid
     * @return string Balance due
     */
    public function calculateBalance(string $totalAmount, string $paidAmount = '0'): string
    {
        return MathHelper::format(
            MathHelper::subtract($totalAmount, $paidAmount),
            2,
            '.',
            ''
        );
    }

    /**
     * Calculate payment status based on paid amount and total.
     *
     * @param  string  $totalAmount  Total amount due
     * @param  string  $paidAmount  Amount already paid
     * @return string Payment status: 'unpaid', 'partially_paid', 'paid', 'overpaid'
     */
    public function determinePaymentStatus(string $totalAmount, string $paidAmount): string
    {
        $comparison = MathHelper::compare($paidAmount, $totalAmount);

        if ($comparison < 0) {
            // Paid less than total
            if (MathHelper::compare($paidAmount, '0') === 0) {
                return 'unpaid';
            }

            return 'partially_paid';
        } elseif ($comparison === 0) {
            // Paid exactly the total
            return 'paid';
        } else {
            // Paid more than total
            return 'overpaid';
        }
    }

    /**
     * Calculate tax amount for a given base amount and tax rate.
     *
     * @param  string  $amount  Base amount
     * @param  string  $taxRate  Tax rate as percentage (e.g., '10' for 10%)
     * @return string Tax amount
     */
    public function calculateTax(string $amount, string $taxRate): string
    {
        return MathHelper::format(
            MathHelper::percentage($amount, $taxRate),
            2,
            '.',
            ''
        );
    }

    /**
     * Calculate discount amount from percentage.
     *
     * @param  string  $amount  Original amount
     * @param  string  $discountPercent  Discount percentage
     * @return string Discount amount
     */
    public function calculateDiscountFromPercent(string $amount, string $discountPercent): string
    {
        return MathHelper::format(
            MathHelper::percentage($amount, $discountPercent),
            2,
            '.',
            ''
        );
    }

    /**
     * Calculate amount after applying discount.
     *
     * @param  string  $amount  Original amount
     * @param  string  $discountAmount  Discount amount to subtract
     * @return string Amount after discount
     */
    public function applyDiscount(string $amount, string $discountAmount): string
    {
        return MathHelper::format(
            MathHelper::subtract($amount, $discountAmount),
            2,
            '.',
            ''
        );
    }

    /**
     * Calculate grand total with all adjustments.
     *
     * @param  string  $subtotal  Subtotal before adjustments
     * @param  string  $taxAmount  Tax amount
     * @param  string  $shippingAmount  Shipping amount
     * @param  string  $discountAmount  Discount amount
     * @return string Grand total
     */
    public function calculateGrandTotal(
        string $subtotal,
        string $taxAmount = '0',
        string $shippingAmount = '0',
        string $discountAmount = '0'
    ): string {
        $total = $subtotal;
        $total = MathHelper::add($total, $taxAmount);
        $total = MathHelper::add($total, $shippingAmount);
        $total = MathHelper::subtract($total, $discountAmount);

        return MathHelper::format($total, 2, '.', '');
    }

    /**
     * Recalculate totals when items change.
     *
     * @param  array  $existingData  Existing document data
     * @param  array  $newItems  New line items
     * @return array Updated totals
     */
    public function recalculate(array $existingData, array $newItems): array
    {
        return $this->calculateLineTotals($newItems, $existingData);
    }

    /**
     * Validate that calculated total matches expected total.
     *
     * @param  string  $calculatedTotal  Calculated total
     * @param  string  $expectedTotal  Expected total
     * @param  string  $tolerance  Acceptable difference (default: '0.01')
     * @return bool True if within tolerance
     */
    public function validateTotal(
        string $calculatedTotal,
        string $expectedTotal,
        string $tolerance = '0.01'
    ): bool {
        $difference = MathHelper::abs(
            MathHelper::subtract($calculatedTotal, $expectedTotal)
        );

        return MathHelper::compare($difference, $tolerance) <= 0;
    }
}
