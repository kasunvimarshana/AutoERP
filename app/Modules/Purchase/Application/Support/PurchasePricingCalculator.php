<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\Support;

use Modules\Sales\Application\Support\SalesTaxCalculator;

/**
 * Normalizes purchase order and invoice payloads by:
 *  - Computing line totals from quantity × unit_price minus discount
 *  - Applying order-level basket/total discount strategies
 *  - Delegating tax computation to the shared SalesTaxCalculator (Tax module)
 *
 * All monetary arithmetic uses bcmath at 6 decimal places.
 */
final class PurchasePricingCalculator
{
    public function __construct(
        private readonly ?SalesTaxCalculator $taxCalculator = null,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function normalizeOrderPayload(array $payload): array
    {
        $normalized = $payload;
        $lines = isset($payload['lines']) && is_array($payload['lines']) ? $payload['lines'] : null;

        if ($lines === null) {
            return $normalized;
        }

        $strategy = $this->discountStrategy($payload['metadata'] ?? null);
        $lineDiscountsEnabled = in_array($strategy, ['unit', 'hybrid', 'basket'], true);
        $stackingEnabled = $this->isStackingEnabled($payload['metadata'] ?? null);
        $lineDiscountTotal = '0.000000';
        $subtotal = '0.000000';

        $normalizedLines = [];
        foreach ($lines as $line) {
            if (! is_array($line)) {
                continue;
            }

            $quantity = $this->decimal($line['ordered_qty'] ?? '0.000000');
            $unitPrice = $this->decimal($line['unit_price'] ?? '0.000000');

            $gross = bcmul($quantity, $unitPrice, 6);
            $lineDiscountPct = $lineDiscountsEnabled ? $this->normalizePercent($line['discount_pct'] ?? '0.000000') : '0.000000';
            $lineDiscountAmount = bcdiv(bcmul($gross, $lineDiscountPct, 6), '100', 6);
            $lineNet = bcsub($gross, $lineDiscountAmount, 6);

            $line['discount_pct'] = $lineDiscountPct;
            $line['line_total'] = $lineNet;

            $lineDiscountTotal = bcadd($lineDiscountTotal, $lineDiscountAmount, 6);
            $subtotal = bcadd($subtotal, $gross, 6);
            $normalizedLines[] = $line;
        }

        $baseForOrderDiscount = $stackingEnabled ? bcsub($subtotal, $lineDiscountTotal, 6) : $subtotal;
        $orderDiscount = $this->resolveBasketDiscount($baseForOrderDiscount, $payload['metadata'] ?? null, $strategy);

        if (! $stackingEnabled && in_array($strategy, ['total', 'basket'], true)) {
            $lineDiscountTotal = '0.000000';
        }

        $discountTotal = bcadd($lineDiscountTotal, $orderDiscount, 6);

        $onDate = new \DateTimeImmutable;
        $tenantId = isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : 0;
        if ($this->taxCalculator !== null) {
            $taxResult = $this->taxCalculator->calculateForLines($normalizedLines, $tenantId, $onDate);
            $normalizedLines = $taxResult['lines'];
            $taxTotal = $taxResult['tax_total'];
        } else {
            $taxTotal = $this->decimal($payload['tax_total'] ?? '0.000000');
        }

        $grandTotal = bcadd(bcsub($subtotal, $discountTotal, 6), $taxTotal, 6);

        $normalized['lines'] = $normalizedLines;
        $normalized['subtotal'] = $subtotal;
        $normalized['tax_total'] = $taxTotal;
        $normalized['discount_total'] = $discountTotal;
        $normalized['grand_total'] = $grandTotal;

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function normalizeInvoicePayload(array $payload): array
    {
        $normalized = $payload;
        $lines = isset($payload['lines']) && is_array($payload['lines']) ? $payload['lines'] : null;

        if ($lines === null) {
            return $normalized;
        }

        $strategy = $this->discountStrategy($payload['metadata'] ?? null);
        $lineDiscountsEnabled = in_array($strategy, ['unit', 'hybrid', 'basket'], true);
        $stackingEnabled = $this->isStackingEnabled($payload['metadata'] ?? null);
        $lineDiscountTotal = '0.000000';
        $subtotal = '0.000000';

        $normalizedLines = [];
        foreach ($lines as $line) {
            if (! is_array($line)) {
                continue;
            }

            $quantity = $this->decimal($line['quantity'] ?? '0.000000');
            $unitPrice = $this->decimal($line['unit_price'] ?? '0.000000');

            $gross = bcmul($quantity, $unitPrice, 6);
            $lineDiscountPct = $lineDiscountsEnabled ? $this->normalizePercent($line['discount_pct'] ?? '0.000000') : '0.000000';
            $lineDiscountAmount = bcdiv(bcmul($gross, $lineDiscountPct, 6), '100', 6);
            $lineNet = bcsub($gross, $lineDiscountAmount, 6);
            $lineTax = $this->decimal($line['tax_amount'] ?? '0.000000');

            $line['discount_pct'] = $lineDiscountPct;
            $line['line_total'] = $lineNet;
            $line['tax_amount'] = $lineTax;

            $lineDiscountTotal = bcadd($lineDiscountTotal, $lineDiscountAmount, 6);
            $subtotal = bcadd($subtotal, $gross, 6);
            $normalizedLines[] = $line;
        }

        $baseForOrderDiscount = $stackingEnabled ? bcsub($subtotal, $lineDiscountTotal, 6) : $subtotal;
        $orderDiscount = $this->resolveBasketDiscount($baseForOrderDiscount, $payload['metadata'] ?? null, $strategy);

        if (! $stackingEnabled && in_array($strategy, ['total', 'basket'], true)) {
            $lineDiscountTotal = '0.000000';
        }

        $discountTotal = bcadd($lineDiscountTotal, $orderDiscount, 6);

        $onDate = new \DateTimeImmutable;
        $tenantId = isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : 0;
        if ($this->taxCalculator !== null) {
            $taxResult = $this->taxCalculator->calculateForLines($normalizedLines, $tenantId, $onDate);
            $normalizedLines = $taxResult['lines'];
            $taxTotal = $taxResult['tax_total'];
        } else {
            $taxTotal = '0.000000';
            foreach ($normalizedLines as $line) {
                $taxTotal = bcadd($taxTotal, $this->decimal($line['tax_amount'] ?? '0.000000'), 6);
            }
        }

        $grandTotal = bcadd(bcsub($subtotal, $discountTotal, 6), $taxTotal, 6);

        $normalized['lines'] = $normalizedLines;
        $normalized['subtotal'] = $subtotal;
        $normalized['tax_total'] = $taxTotal;
        $normalized['discount_total'] = $discountTotal;
        $normalized['grand_total'] = $grandTotal;

        return $normalized;
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    private function discountStrategy(?array $metadata): string
    {
        $strategy = is_array($metadata) ? (string) ($metadata['discount_strategy'] ?? '') : '';
        $strategy = strtolower(trim($strategy));

        return in_array($strategy, ['unit', 'total', 'hybrid', 'basket'], true) ? $strategy : 'unit';
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    private function isStackingEnabled(?array $metadata): bool
    {
        if (! is_array($metadata) || ! array_key_exists('stack_discounts', $metadata)) {
            return true;
        }

        return (bool) $metadata['stack_discounts'];
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    private function resolveBasketDiscount(string $base, ?array $metadata, string $strategy): string
    {
        if (! in_array($strategy, ['total', 'hybrid', 'basket'], true)) {
            return '0.000000';
        }

        if (! is_array($metadata)) {
            return '0.000000';
        }

        $value = $this->decimal($metadata['discount_value'] ?? '0.000000');
        if (bccomp($value, '0.000000', 6) <= 0) {
            return '0.000000';
        }

        $type = strtolower((string) ($metadata['discount_type'] ?? 'percentage'));
        if ($type === 'fixed') {
            return bccomp($value, $base, 6) > 0 ? $base : $value;
        }

        $pct = $this->normalizePercent($value);

        return bcdiv(bcmul($base, $pct, 6), '100', 6);
    }

    private function normalizePercent(string $value): string
    {
        $normalized = $this->decimal($value);
        if (bccomp($normalized, '0.000000', 6) < 0) {
            return '0.000000';
        }
        if (bccomp($normalized, '100.000000', 6) > 0) {
            return '100.000000';
        }

        return $normalized;
    }

    private function decimal(mixed $value): string
    {
        if (is_string($value) || is_int($value) || is_float($value)) {
            return number_format((float) $value, 6, '.', '');
        }

        return '0.000000';
    }
}
