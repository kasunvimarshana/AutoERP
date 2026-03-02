<?php

declare(strict_types=1);

namespace Modules\Inventory\Tests\Unit;

use Modules\Inventory\Domain\ValueObjects\Quantity;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Quantity value object.
 * All arithmetic must use BCMath and maintain 4-decimal precision.
 */
class QuantityTest extends TestCase
{
    // ── Construction ─────────────────────────────────────────────────────

    public function test_integer_string_is_stored_with_four_decimal_scale(): void
    {
        $qty = new Quantity('5');

        $this->assertSame('5.0000', $qty->getAmount());
    }

    public function test_decimal_string_is_accepted(): void
    {
        $qty = new Quantity('2.5');

        $this->assertSame('2.5000', $qty->getAmount());
    }

    public function test_zero_quantity_is_valid(): void
    {
        $qty = new Quantity('0');

        $this->assertSame('0.0000', $qty->getAmount());
    }

    public function test_negative_quantity_is_valid(): void
    {
        // Negative quantities are allowed (e.g. adjustments, returns)
        $qty = new Quantity('-1.5');

        $this->assertSame('-1.5000', $qty->getAmount());
    }

    public function test_non_numeric_value_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Quantity('abc');
    }

    public function test_empty_string_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Quantity('');
    }

    // ── Arithmetic ────────────────────────────────────────────────────────

    public function test_add_sums_two_quantities(): void
    {
        $a = new Quantity('3.0000');
        $b = new Quantity('2.0000');

        $result = $a->add($b);

        $this->assertSame('5.0000', $result->getAmount());
    }

    public function test_subtract_gives_difference(): void
    {
        $a = new Quantity('10.0000');
        $b = new Quantity('4.0000');

        $result = $a->subtract($b);

        $this->assertSame('6.0000', $result->getAmount());
    }

    public function test_subtract_can_yield_negative_result(): void
    {
        $a = new Quantity('2.0000');
        $b = new Quantity('5.0000');

        $result = $a->subtract($b);

        $this->assertSame('-3.0000', $result->getAmount());
    }

    public function test_add_preserves_bcmath_precision(): void
    {
        // 1/3 * 3 must be exactly 1.0000
        $a = new Quantity('0.3333');
        $b = new Quantity('0.6667');

        $result = $a->add($b);

        $this->assertSame(0, bccomp($result->getAmount(), '1.0000', 4));
    }

    // ── Predicates ───────────────────────────────────────────────────────

    public function test_is_negative_true_for_negative_amount(): void
    {
        $qty = new Quantity('-0.0001');

        $this->assertTrue($qty->isNegative());
    }

    public function test_is_negative_false_for_zero(): void
    {
        $this->assertFalse((new Quantity('0'))->isNegative());
    }

    public function test_is_negative_false_for_positive(): void
    {
        $this->assertFalse((new Quantity('1'))->isNegative());
    }

    public function test_is_zero_true_for_zero(): void
    {
        $this->assertTrue((new Quantity('0.0000'))->isZero());
    }

    public function test_is_zero_false_for_non_zero(): void
    {
        $this->assertFalse((new Quantity('0.0001'))->isZero());
    }

    public function test_is_greater_than_true_when_larger(): void
    {
        $a = new Quantity('5.0000');
        $b = new Quantity('3.0000');

        $this->assertTrue($a->isGreaterThan($b));
        $this->assertFalse($b->isGreaterThan($a));
    }

    public function test_is_greater_than_false_for_equal_values(): void
    {
        $a = new Quantity('5.0000');
        $b = new Quantity('5.0000');

        $this->assertFalse($a->isGreaterThan($b));
    }

    public function test_is_greater_than_or_equal_true_for_equal_values(): void
    {
        $a = new Quantity('5.0000');
        $b = new Quantity('5.0000');

        $this->assertTrue($a->isGreaterThanOrEqual($b));
    }

    public function test_is_greater_than_or_equal_true_when_greater(): void
    {
        $a = new Quantity('6.0000');
        $b = new Quantity('5.0000');

        $this->assertTrue($a->isGreaterThanOrEqual($b));
    }

    public function test_is_greater_than_or_equal_false_when_less(): void
    {
        $a = new Quantity('4.0000');
        $b = new Quantity('5.0000');

        $this->assertFalse($a->isGreaterThanOrEqual($b));
    }

    // ── Immutability ──────────────────────────────────────────────────────

    public function test_arithmetic_does_not_mutate_original(): void
    {
        $original = new Quantity('10.0000');
        $original->add(new Quantity('5.0000'));

        $this->assertSame('10.0000', $original->getAmount());
    }

    // ── String representation ─────────────────────────────────────────────

    public function test_to_string_returns_scaled_amount(): void
    {
        $qty = new Quantity('3');

        $this->assertSame('3.0000', (string) $qty);
    }
}
