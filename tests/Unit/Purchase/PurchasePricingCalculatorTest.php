<?php

declare(strict_types=1);

namespace Tests\Unit\Purchase;

use Modules\Purchase\Application\Support\PurchasePricingCalculator;
use PHPUnit\Framework\TestCase;

final class PurchasePricingCalculatorTest extends TestCase
{
    // -------------------------------------------------------------------------
    // normalizeOrderPayload — no lines
    // -------------------------------------------------------------------------

    public function test_returns_payload_unchanged_when_no_lines_key(): void
    {
        $calculator = new PurchasePricingCalculator;

        $payload = ['tenant_id' => 1, 'supplier_id' => 2];
        $result = $calculator->normalizeOrderPayload($payload);

        $this->assertSame($payload, $result);
    }

    // -------------------------------------------------------------------------
    // normalizeOrderPayload — unit discount strategy (default)
    // -------------------------------------------------------------------------

    public function test_order_unit_discount_calculates_totals(): void
    {
        $calculator = new PurchasePricingCalculator;

        $result = $calculator->normalizeOrderPayload([
            'tenant_id' => 1,
            'tax_total' => '5.000000',
            'metadata' => ['discount_strategy' => 'unit'],
            'lines' => [
                ['ordered_qty' => '2', 'unit_price' => '100', 'discount_pct' => '10'],
                ['ordered_qty' => '1', 'unit_price' => '50', 'discount_pct' => '0'],
            ],
        ]);

        $this->assertSame('250.000000', $result['subtotal']);
        $this->assertSame('20.000000', $result['discount_total']);
        // tax_total preserved from payload (no tax calculator injected)
        $this->assertSame('5.000000', $result['tax_total']);
        $this->assertSame('235.000000', $result['grand_total']);
        $this->assertSame('180.000000', $result['lines'][0]['line_total']);
    }

    // -------------------------------------------------------------------------
    // normalizeOrderPayload — basket (total) discount strategy
    // -------------------------------------------------------------------------

    public function test_order_total_discount_strategy_applies_basket_discount(): void
    {
        $calculator = new PurchasePricingCalculator;

        $result = $calculator->normalizeOrderPayload([
            'tenant_id' => 1,
            'metadata' => [
                'discount_strategy' => 'total',
                'discount_type' => 'percentage',
                'discount_value' => '10',
                'stack_discounts' => false,
            ],
            'lines' => [
                ['ordered_qty' => '1', 'unit_price' => '200', 'discount_pct' => '50'],
            ],
        ]);

        // subtotal = 200, line discount should be suppressed (strategy=total, no stacking)
        $this->assertSame('200.000000', $result['subtotal']);
        // basket discount = 10% of 200 = 20
        $this->assertSame('20.000000', $result['discount_total']);
        $this->assertSame('0.000000', $result['tax_total']);
        $this->assertSame('180.000000', $result['grand_total']);
    }

    // -------------------------------------------------------------------------
    // normalizeOrderPayload — hybrid strategy (both line + basket)
    // -------------------------------------------------------------------------

    public function test_order_hybrid_strategy_stacks_line_and_basket_discounts(): void
    {
        $calculator = new PurchasePricingCalculator;

        $result = $calculator->normalizeOrderPayload([
            'tenant_id' => 1,
            'metadata' => [
                'discount_strategy' => 'hybrid',
                'discount_type' => 'percentage',
                'discount_value' => '5',
                'stack_discounts' => true,
            ],
            'lines' => [
                ['ordered_qty' => '2', 'unit_price' => '100', 'discount_pct' => '10'],
            ],
        ]);

        // gross = 200, line discount = 20, line net = 180
        // base for basket = 200 - 20 = 180 (stacking enabled)
        // basket discount = 5% of 180 = 9
        // total discount = 20 + 9 = 29
        $this->assertSame('200.000000', $result['subtotal']);
        $this->assertSame('29.000000', $result['discount_total']);
        $this->assertSame('171.000000', $result['grand_total']);
    }

    // -------------------------------------------------------------------------
    // normalizeOrderPayload — fixed basket discount
    // -------------------------------------------------------------------------

    public function test_order_fixed_basket_discount(): void
    {
        $calculator = new PurchasePricingCalculator;

        $result = $calculator->normalizeOrderPayload([
            'tenant_id' => 1,
            'metadata' => [
                'discount_strategy' => 'basket',
                'discount_type' => 'fixed',
                'discount_value' => '15',
            ],
            'lines' => [
                ['ordered_qty' => '1', 'unit_price' => '100'],
            ],
        ]);

        $this->assertSame('100.000000', $result['subtotal']);
        $this->assertSame('15.000000', $result['discount_total']);
        $this->assertSame('85.000000', $result['grand_total']);
    }

    // -------------------------------------------------------------------------
    // normalizeOrderPayload — non-array line entries are skipped
    // -------------------------------------------------------------------------

    public function test_order_skips_non_array_line_entries(): void
    {
        $calculator = new PurchasePricingCalculator;

        $result = $calculator->normalizeOrderPayload([
            'tenant_id' => 1,
            'lines' => [
                'not-an-array',
                ['ordered_qty' => '1', 'unit_price' => '100'],
            ],
        ]);

        $this->assertCount(1, $result['lines']);
        $this->assertSame('100.000000', $result['subtotal']);
    }

    // -------------------------------------------------------------------------
    // normalizeOrderPayload — zero values
    // -------------------------------------------------------------------------

    public function test_order_zero_values_produce_zero_totals(): void
    {
        $calculator = new PurchasePricingCalculator;

        $result = $calculator->normalizeOrderPayload([
            'tenant_id' => 1,
            'lines' => [
                ['ordered_qty' => '0', 'unit_price' => '100'],
                ['ordered_qty' => '1', 'unit_price' => '0'],
            ],
        ]);

        $this->assertSame('0.000000', $result['subtotal']);
        $this->assertSame('0.000000', $result['discount_total']);
        $this->assertSame('0.000000', $result['grand_total']);
    }

    // -------------------------------------------------------------------------
    // normalizeInvoicePayload — no lines
    // -------------------------------------------------------------------------

    public function test_invoice_returns_payload_unchanged_when_no_lines_key(): void
    {
        $calculator = new PurchasePricingCalculator;

        $payload = ['tenant_id' => 1, 'supplier_id' => 2];
        $result = $calculator->normalizeInvoicePayload($payload);

        $this->assertSame($payload, $result);
    }

    // -------------------------------------------------------------------------
    // normalizeInvoicePayload — basic line totals
    // -------------------------------------------------------------------------

    public function test_invoice_calculates_line_totals_and_grand_total(): void
    {
        $calculator = new PurchasePricingCalculator;

        $result = $calculator->normalizeInvoicePayload([
            'tenant_id' => 1,
            'metadata' => ['discount_strategy' => 'unit'],
            'lines' => [
                ['quantity' => '3', 'unit_price' => '50', 'discount_pct' => '0', 'tax_amount' => '15'],
                ['quantity' => '2', 'unit_price' => '25', 'discount_pct' => '20', 'tax_amount' => '4'],
            ],
        ]);

        // line 1: 3*50 = 150, no discount, net=150
        // line 2: 2*25 = 50, 20% discount=10, net=40
        $this->assertSame('200.000000', $result['subtotal']);
        $this->assertSame('10.000000', $result['discount_total']);
        // tax = sum of line tax_amounts = 15+4 = 19
        $this->assertSame('19.000000', $result['tax_total']);
        $this->assertSame('209.000000', $result['grand_total']);
    }

    // -------------------------------------------------------------------------
    // normalizeInvoicePayload — hybrid strategy
    // -------------------------------------------------------------------------

    public function test_invoice_hybrid_strategy_applies_both_line_and_basket_discounts(): void
    {
        $calculator = new PurchasePricingCalculator;

        $result = $calculator->normalizeInvoicePayload([
            'tenant_id' => 1,
            'metadata' => [
                'discount_strategy' => 'hybrid',
                'discount_type' => 'percentage',
                'discount_value' => '5',
                'stack_discounts' => true,
            ],
            'lines' => [
                ['quantity' => '2', 'unit_price' => '100', 'discount_pct' => '10', 'tax_amount' => '18'],
            ],
        ]);

        $this->assertSame('200.000000', $result['subtotal']);
        $this->assertSame('29.000000', $result['discount_total']);
        $this->assertSame('18.000000', $result['tax_total']);
        $this->assertSame('189.000000', $result['grand_total']);
    }

    // -------------------------------------------------------------------------
    // normalizeInvoicePayload — discount_pct capped at 100
    // -------------------------------------------------------------------------

    public function test_invoice_discount_pct_capped_at_100(): void
    {
        $calculator = new PurchasePricingCalculator;

        $result = $calculator->normalizeInvoicePayload([
            'tenant_id' => 1,
            'lines' => [
                ['quantity' => '1', 'unit_price' => '100', 'discount_pct' => '200'],
            ],
        ]);

        // capped to 100%, so discount = 100, net = 0
        $this->assertSame('100.000000', $result['subtotal']);
        $this->assertSame('100.000000', $result['discount_total']);
        $this->assertSame('0.000000', $result['grand_total']);
    }

    // -------------------------------------------------------------------------
    // normalizeOrderPayload — discount_pct capped at 100
    // -------------------------------------------------------------------------

    public function test_order_discount_pct_capped_at_100(): void
    {
        $calculator = new PurchasePricingCalculator;

        $result = $calculator->normalizeOrderPayload([
            'tenant_id' => 1,
            'lines' => [
                ['ordered_qty' => '1', 'unit_price' => '100', 'discount_pct' => '150'],
            ],
        ]);

        $this->assertSame('100.000000', $result['subtotal']);
        $this->assertSame('100.000000', $result['discount_total']);
        $this->assertSame('0.000000', $result['grand_total']);
    }

    // -------------------------------------------------------------------------
    // normalizeOrderPayload — fixed basket capped at subtotal
    // -------------------------------------------------------------------------

    public function test_order_fixed_basket_discount_capped_at_subtotal(): void
    {
        $calculator = new PurchasePricingCalculator;

        $result = $calculator->normalizeOrderPayload([
            'tenant_id' => 1,
            'metadata' => [
                'discount_strategy' => 'basket',
                'discount_type' => 'fixed',
                'discount_value' => '9999',
            ],
            'lines' => [
                ['ordered_qty' => '1', 'unit_price' => '100'],
            ],
        ]);

        // fixed discount capped at subtotal = 100
        $this->assertSame('100.000000', $result['discount_total']);
        $this->assertSame('0.000000', $result['grand_total']);
    }

    // -------------------------------------------------------------------------
    // normalizeOrderPayload — unknown strategy defaults to 'unit'
    // -------------------------------------------------------------------------

    public function test_order_unknown_strategy_defaults_to_unit(): void
    {
        $calculator = new PurchasePricingCalculator;

        $result = $calculator->normalizeOrderPayload([
            'tenant_id' => 1,
            'metadata' => ['discount_strategy' => 'unknown'],
            'lines' => [
                ['ordered_qty' => '1', 'unit_price' => '100', 'discount_pct' => '20'],
            ],
        ]);

        // unit strategy applies line discount = 20
        $this->assertSame('20.000000', $result['discount_total']);
    }

    // -------------------------------------------------------------------------
    // normalizeOrderPayload — multiple lines
    // -------------------------------------------------------------------------

    public function test_order_multi_line_totalling(): void
    {
        $calculator = new PurchasePricingCalculator;

        $result = $calculator->normalizeOrderPayload([
            'tenant_id' => 1,
            'lines' => [
                ['ordered_qty' => '5', 'unit_price' => '10', 'discount_pct' => '0'],
                ['ordered_qty' => '3', 'unit_price' => '20', 'discount_pct' => '10'],
                ['ordered_qty' => '2', 'unit_price' => '15', 'discount_pct' => '5'],
            ],
        ]);

        // line1: 50, line2: 60-6=54, line3: 30-1.5=28.5
        // subtotal = 50+60+30 = 140
        // discounts = 0+6+1.5 = 7.5
        $this->assertSame('140.000000', $result['subtotal']);
        $this->assertSame('7.500000', $result['discount_total']);
        $this->assertSame('132.500000', $result['grand_total']);
    }
}
