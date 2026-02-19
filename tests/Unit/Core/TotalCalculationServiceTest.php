<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use Modules\Core\Services\TotalCalculationService;
use Tests\TestCase;

class TotalCalculationServiceTest extends TestCase
{
    private TotalCalculationService $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new TotalCalculationService;
    }

    public function test_can_calculate_line_totals_without_tax(): void
    {
        $items = [
            ['quantity' => '2', 'unit_price' => '100.00', 'discount_amount' => '10.00'],
            ['quantity' => '1', 'unit_price' => '50.00', 'discount_amount' => '0.00'],
        ];

        $totals = $this->calculator->calculateLineTotals($items);

        $this->assertEquals('240.00', $totals['subtotal']); // (2*100-10) + (1*50) = 190 + 50
        $this->assertEquals('0.00', $totals['tax_amount']);
        $this->assertEquals('240.00', $totals['total_amount']);
    }

    public function test_can_calculate_line_totals_with_tax(): void
    {
        $items = [
            ['quantity' => '1', 'unit_price' => '100.00', 'discount_amount' => '0', 'tax_rate' => '10'],
        ];

        $totals = $this->calculator->calculateLineTotals($items);

        $this->assertEquals('100.00', $totals['subtotal']);
        $this->assertEquals('10.00', $totals['tax_amount']);
        $this->assertEquals('110.00', $totals['total_amount']);
    }

    public function test_can_calculate_with_document_level_discount(): void
    {
        $items = [
            ['quantity' => '1', 'unit_price' => '100.00', 'discount_amount' => '0'],
        ];

        $documentData = [
            'discount_amount' => '20.00',
        ];

        $totals = $this->calculator->calculateLineTotals($items, $documentData);

        $this->assertEquals('80.00', $totals['subtotal']); // 100 - 20 discount
        $this->assertEquals('20.00', $totals['discount_amount']);
    }

    public function test_can_calculate_with_shipping(): void
    {
        $items = [
            ['quantity' => '1', 'unit_price' => '100.00', 'discount_amount' => '0'],
        ];

        $documentData = [
            'shipping_amount' => '15.00',
        ];

        $totals = $this->calculator->calculateLineTotals($items, $documentData);

        $this->assertEquals('115.00', $totals['subtotal']); // 100 + 15 shipping
        $this->assertEquals('15.00', $totals['shipping_amount']);
    }

    public function test_can_calculate_balance(): void
    {
        $balance = $this->calculator->calculateBalance('100.00', '30.00');

        $this->assertEquals('70.00', $balance);
    }

    public function test_can_determine_unpaid_status(): void
    {
        $status = $this->calculator->determinePaymentStatus('100.00', '0');

        $this->assertEquals('unpaid', $status);
    }

    public function test_can_determine_partially_paid_status(): void
    {
        $status = $this->calculator->determinePaymentStatus('100.00', '50.00');

        $this->assertEquals('partially_paid', $status);
    }

    public function test_can_determine_paid_status(): void
    {
        $status = $this->calculator->determinePaymentStatus('100.00', '100.00');

        $this->assertEquals('paid', $status);
    }

    public function test_can_determine_overpaid_status(): void
    {
        $status = $this->calculator->determinePaymentStatus('100.00', '150.00');

        $this->assertEquals('overpaid', $status);
    }

    public function test_can_calculate_tax(): void
    {
        $tax = $this->calculator->calculateTax('100.00', '15');

        $this->assertEquals('15.00', $tax);
    }

    public function test_can_calculate_discount_from_percent(): void
    {
        $discount = $this->calculator->calculateDiscountFromPercent('200.00', '10');

        $this->assertEquals('20.00', $discount);
    }

    public function test_can_apply_discount(): void
    {
        $amount = $this->calculator->applyDiscount('100.00', '25.00');

        $this->assertEquals('75.00', $amount);
    }

    public function test_can_calculate_grand_total(): void
    {
        $total = $this->calculator->calculateGrandTotal(
            '100.00', // subtotal
            '10.00',  // tax
            '5.00',   // shipping
            '15.00'   // discount
        );

        $this->assertEquals('100.00', $total); // 100 + 10 + 5 - 15
    }

    public function test_can_validate_correct_total(): void
    {
        $isValid = $this->calculator->validateTotal('100.00', '100.00');

        $this->assertTrue($isValid);
    }

    public function test_can_validate_total_within_tolerance(): void
    {
        $isValid = $this->calculator->validateTotal('100.00', '100.005', '0.01');

        $this->assertTrue($isValid);
    }

    public function test_can_detect_invalid_total(): void
    {
        $isValid = $this->calculator->validateTotal('100.00', '110.00', '0.01');

        $this->assertFalse($isValid);
    }

    public function test_handles_complex_calculation(): void
    {
        $items = [
            ['quantity' => '3', 'unit_price' => '25.00', 'discount_amount' => '5.00', 'tax_rate' => '10'],
            ['quantity' => '2', 'unit_price' => '15.00', 'discount_amount' => '0', 'tax_rate' => '10'],
        ];

        $documentData = [
            'shipping_amount' => '10.00',
            'discount_amount' => '5.00',
        ];

        $totals = $this->calculator->calculateLineTotals($items, $documentData);

        // Item 1: (3 * 25 - 5) = 70, tax = 7
        // Item 2: (2 * 15 - 0) = 30, tax = 3
        // Subtotal: 70 + 30 + 10 shipping - 5 discount = 105
        // Tax: 7 + 3 = 10
        // Total: 105 + 10 = 115

        $this->assertEquals('105.00', $totals['subtotal']);
        $this->assertEquals('10.00', $totals['tax_amount']);
        $this->assertEquals('115.00', $totals['total_amount']);
    }

    public function test_handles_zero_values(): void
    {
        $items = [
            ['quantity' => '0', 'unit_price' => '100.00', 'discount_amount' => '0'],
        ];

        $totals = $this->calculator->calculateLineTotals($items);

        $this->assertEquals('0.00', $totals['subtotal']);
        $this->assertEquals('0.00', $totals['tax_amount']);
        $this->assertEquals('0.00', $totals['total_amount']);
    }

    public function test_precision_with_repeating_decimals(): void
    {
        $items = [
            ['quantity' => '3', 'unit_price' => '33.33', 'discount_amount' => '0', 'tax_rate' => '13.5'],
        ];

        $totals = $this->calculator->calculateLineTotals($items);

        // 3 * 33.33 = 99.99
        // Tax: 99.99 * 13.5% = 13.49865
        $this->assertEquals('99.99', $totals['subtotal']);
        $this->assertEquals('13.49', $totals['tax_amount']); // Rounded to 2 decimals
    }
}
