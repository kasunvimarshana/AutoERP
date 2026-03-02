<?php

declare(strict_types=1);

namespace Modules\Product\Tests\Unit;

use Modules\Product\Domain\ValueObjects\Price;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Price value object.
 * All monetary arithmetic must use BCMath (no floating-point).
 */
class PriceTest extends TestCase
{
    // ── Construction ─────────────────────────────────────────────────────

    public function test_of_creates_price_with_correct_amount_and_currency(): void
    {
        $price = Price::of('10.00', 'USD');

        $this->assertSame('10.0000', $price->getAmount());
        $this->assertSame('USD', $price->getCurrency());
    }

    public function test_currency_is_uppercased(): void
    {
        $price = Price::of('1.00', 'usd');

        $this->assertSame('USD', $price->getCurrency());
    }

    public function test_amount_is_stored_with_four_decimal_scale(): void
    {
        $price = Price::of('5', 'GBP');

        $this->assertSame('5.0000', $price->getAmount());
    }

    public function test_zero_amount_is_valid(): void
    {
        $price = Price::of('0', 'EUR');

        $this->assertSame('0.0000', $price->getAmount());
    }

    public function test_negative_amount_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Price::of('-1.00', 'USD');
    }

    public function test_non_numeric_amount_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Price::of('abc', 'USD');
    }

    public function test_currency_too_short_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Price::of('10.00', 'US');
    }

    public function test_currency_too_long_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Price::of('10.00', 'USDT');
    }

    // ── Arithmetic ────────────────────────────────────────────────────────

    public function test_add_sums_two_prices(): void
    {
        $a = Price::of('10.0000', 'USD');
        $b = Price::of('5.0000', 'USD');

        $result = $a->add($b);

        $this->assertSame('15.0000', $result->getAmount());
        $this->assertSame('USD', $result->getCurrency());
    }

    public function test_add_currency_mismatch_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Price::of('1.00', 'USD')->add(Price::of('1.00', 'EUR'));
    }

    public function test_multiply_scales_amount(): void
    {
        $price  = Price::of('5.0000', 'USD');
        $result = $price->multiply('3');

        $this->assertSame('15.0000', $result->getAmount());
    }

    public function test_multiply_by_fractional_factor(): void
    {
        $price  = Price::of('10.0000', 'USD');
        $result = $price->multiply('0.5');

        $this->assertSame('5.0000', $result->getAmount());
    }

    // ── Comparison ────────────────────────────────────────────────────────

    public function test_is_greater_than_returns_true_when_larger(): void
    {
        $a = Price::of('10.00', 'USD');
        $b = Price::of('5.00', 'USD');

        $this->assertTrue($a->isGreaterThan($b));
        $this->assertFalse($b->isGreaterThan($a));
    }

    public function test_is_greater_than_returns_false_for_equal_amounts(): void
    {
        $a = Price::of('10.00', 'USD');
        $b = Price::of('10.00', 'USD');

        $this->assertFalse($a->isGreaterThan($b));
    }

    public function test_is_equal_to_returns_true_for_same_value(): void
    {
        $a = Price::of('10.0000', 'USD');
        $b = Price::of('10.00', 'USD');

        $this->assertTrue($a->isEqualTo($b));
    }

    public function test_is_equal_to_returns_false_for_different_value(): void
    {
        $a = Price::of('10.0001', 'USD');
        $b = Price::of('10.0000', 'USD');

        $this->assertFalse($a->isEqualTo($b));
    }

    public function test_comparison_currency_mismatch_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Price::of('10.00', 'USD')->isGreaterThan(Price::of('10.00', 'EUR'));
    }

    // ── Immutability ──────────────────────────────────────────────────────

    public function test_arithmetic_does_not_mutate_original(): void
    {
        $original = Price::of('10.0000', 'USD');
        $original->add(Price::of('5.0000', 'USD'));

        $this->assertSame('10.0000', $original->getAmount());
    }

    // ── String representation ─────────────────────────────────────────────

    public function test_to_string_includes_currency_and_amount(): void
    {
        $price = Price::of('42.50', 'EUR');

        $this->assertSame('EUR 42.5000', (string) $price);
    }
}
