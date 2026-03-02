<?php

declare(strict_types=1);

namespace Modules\Accounting\Tests\Unit;

use Modules\Accounting\Domain\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Money value object, which uses BCMath for all arithmetic.
 * Financial precision is mandatory per the platform's coding standards.
 */
class MoneyTest extends TestCase
{
    // ── Construction ─────────────────────────────────────────────────────

    public function test_of_creates_money_with_correct_amount_and_currency(): void
    {
        $money = Money::of('100.00', 'USD');

        $this->assertSame('100.0000', $money->getAmount());
        $this->assertSame('USD', $money->getCurrency());
    }

    public function test_currency_is_uppercased(): void
    {
        $money = Money::of('1.00', 'usd');

        $this->assertSame('USD', $money->getCurrency());
    }

    public function test_zero_factory_returns_zero_amount(): void
    {
        $money = Money::zero('GBP');

        $this->assertTrue($money->isZero());
        $this->assertSame('GBP', $money->getCurrency());
    }

    public function test_invalid_amount_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Money::of('not-a-number', 'USD');
    }

    public function test_invalid_currency_length_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Money::of('1.00', 'US');
    }

    // ── Arithmetic ────────────────────────────────────────────────────────

    public function test_add_sums_two_money_values(): void
    {
        $a = Money::of('10.0000', 'USD');
        $b = Money::of('5.0000', 'USD');

        $result = $a->add($b);

        $this->assertSame('15.0000', $result->getAmount());
    }

    public function test_subtract_gives_difference(): void
    {
        $a = Money::of('10.0000', 'USD');
        $b = Money::of('3.0000', 'USD');

        $result = $a->subtract($b);

        $this->assertSame('7.0000', $result->getAmount());
    }

    public function test_multiply_scales_amount(): void
    {
        $money  = Money::of('5.0000', 'USD');
        $result = $money->multiply('3');

        $this->assertSame('15.0000', $result->getAmount());
    }

    public function test_divide_splits_amount(): void
    {
        $money  = Money::of('10.0000', 'USD');
        $result = $money->divide('4');

        $this->assertSame('2.5000', $result->getAmount());
    }

    public function test_divide_by_zero_throws(): void
    {
        $this->expectException(\DivisionByZeroError::class);

        Money::of('10.0000', 'USD')->divide('0');
    }

    public function test_add_currency_mismatch_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Money::of('1.00', 'USD')->add(Money::of('1.00', 'EUR'));
    }

    // ── Comparison ────────────────────────────────────────────────────────

    public function test_is_zero(): void
    {
        $this->assertTrue(Money::of('0', 'USD')->isZero());
        $this->assertFalse(Money::of('0.0001', 'USD')->isZero());
    }

    public function test_is_positive(): void
    {
        $this->assertTrue(Money::of('0.0001', 'USD')->isPositive());
        $this->assertFalse(Money::zero('USD')->isPositive());
    }

    public function test_is_negative(): void
    {
        $this->assertTrue(Money::of('-1', 'USD')->isNegative());
        $this->assertFalse(Money::of('0', 'USD')->isNegative());
    }

    public function test_equals_same_value(): void
    {
        $a = Money::of('10.0000', 'USD');
        $b = Money::of('10.00', 'USD');

        $this->assertTrue($a->equals($b));
    }

    public function test_equals_different_value(): void
    {
        $a = Money::of('10.0000', 'USD');
        $b = Money::of('10.0001', 'USD');

        $this->assertFalse($a->equals($b));
    }

    // ── Immutability ──────────────────────────────────────────────────────

    public function test_arithmetic_does_not_mutate_original(): void
    {
        $original = Money::of('10.0000', 'USD');
        $original->add(Money::of('5.0000', 'USD'));

        $this->assertSame('10.0000', $original->getAmount());
    }

    // ── String representation ─────────────────────────────────────────────

    public function test_to_string_includes_currency_and_amount(): void
    {
        $money = Money::of('42.50', 'EUR');

        $this->assertSame('EUR 42.5000', (string) $money);
    }
}
