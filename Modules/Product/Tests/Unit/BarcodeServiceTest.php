<?php

declare(strict_types=1);

namespace Modules\Product\Tests\Unit;

use Modules\Product\Application\Services\BarcodeService;
use PHPUnit\Framework\TestCase;

/**
 * Tests for BarcodeService — especially EAN-13 check-digit calculation,
 * inspired by the Barcode model in the PHP_POS reference repository.
 */
class BarcodeServiceTest extends TestCase
{
    private BarcodeService $service;

    protected function setUp(): void
    {
        $this->service = new BarcodeService();
    }

    // ── EAN-13 ───────────────────────────────────────────────────────────

    public function test_ean13_generates_13_digit_string(): void
    {
        $result = $this->service->generate('123456789012', 'EAN13');

        $this->assertSame('EAN13', $result['type']);
        $this->assertMatchesRegularExpression('/^\d{13}$/', $result['value']);
    }

    public function test_ean13_check_digit_is_correct(): void
    {
        // Known valid EAN-13: 5901234123457 (check digit = 7)
        $result = $this->service->generate('590123412345', 'EAN13');

        $this->assertTrue($this->service->validate($result['value'], 'EAN13'));
    }

    public function test_ean13_all_zeros_check_digit_is_zero(): void
    {
        // 000000000000 → check = (10 - 0) % 10 = 0
        $result = $this->service->generate('000000000000', 'EAN13');

        $this->assertSame('0000000000000', $result['value']);
    }

    public function test_ean13_validate_returns_true_for_valid_barcode(): void
    {
        // Well-known valid EAN-13
        $this->assertTrue($this->service->validate('4006381333931', 'EAN13'));
    }

    public function test_ean13_validate_returns_false_for_wrong_check_digit(): void
    {
        // Flip last digit: 4006381333930 (should be 1)
        $this->assertFalse($this->service->validate('4006381333930', 'EAN13'));
    }

    public function test_ean13_validate_returns_false_for_non_numeric(): void
    {
        $this->assertFalse($this->service->validate('400638133393X', 'EAN13'));
    }

    public function test_ean13_pads_short_sku_to_12_digits_before_check(): void
    {
        // SKU '1' → base 000000000001, check digit = (10 - (0*9 + 1*1)) % 10
        $result = $this->service->generate('1', 'EAN13');

        $this->assertMatchesRegularExpression('/^\d{13}$/', $result['value']);
        $this->assertTrue($this->service->validate($result['value'], 'EAN13'));
    }

    public function test_ean13_display_contains_dashes(): void
    {
        $result = $this->service->generate('123456789012', 'EAN13');

        $this->assertStringContainsString('-', $result['display']);
    }

    // ── CODE128 ──────────────────────────────────────────────────────────

    public function test_code128_returns_sku_as_value(): void
    {
        $result = $this->service->generate('SKU-ABC-001', 'CODE128');

        $this->assertSame('CODE128', $result['type']);
        $this->assertSame('SKU-ABC-001', $result['value']);
    }

    public function test_code128_validate_returns_true_for_non_empty(): void
    {
        $this->assertTrue($this->service->validate('SKU-001', 'CODE128'));
    }

    public function test_code128_validate_returns_false_for_empty(): void
    {
        $this->assertFalse($this->service->validate('', 'CODE128'));
    }

    // ── QR ───────────────────────────────────────────────────────────────

    public function test_qr_returns_sku_as_value(): void
    {
        $result = $this->service->generate('https://example.com/product/1', 'QR');

        $this->assertSame('QR', $result['type']);
        $this->assertSame('https://example.com/product/1', $result['value']);
    }

    // ── Unsupported type ─────────────────────────────────────────────────

    public function test_unsupported_type_throws_invalid_argument_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->generate('SKU-001', 'PDF417');
    }

    public function test_type_is_case_insensitive(): void
    {
        $result = $this->service->generate('SKU-001', 'ean13');

        $this->assertSame('EAN13', $result['type']);
    }
}
