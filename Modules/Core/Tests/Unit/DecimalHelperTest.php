<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit;

use InvalidArgumentException;
use Modules\Core\Application\Helpers\DecimalHelper;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DecimalHelper (BCMath precision helper).
 *
 * Validates that all financial/quantity arithmetic uses BCMath
 * and produces deterministic, floating-point-free results.
 */
class DecimalHelperTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Addition
    // -------------------------------------------------------------------------

    public function test_add_two_positive_decimals(): void
    {
        $result = DecimalHelper::add('10.1234', '5.6789');
        $this->assertSame('15.8023', $result);
    }

    public function test_add_with_custom_scale(): void
    {
        $result = DecimalHelper::add('1.1', '2.2', 8);
        $this->assertSame('3.30000000', $result);
    }

    // -------------------------------------------------------------------------
    // Subtraction
    // -------------------------------------------------------------------------

    public function test_sub_positive_result(): void
    {
        $result = DecimalHelper::sub('10.0000', '3.0000');
        $this->assertSame('7.0000', $result);
    }

    public function test_sub_negative_result(): void
    {
        $result = DecimalHelper::sub('3.0000', '10.0000');
        $this->assertSame('-7.0000', $result);
    }

    // -------------------------------------------------------------------------
    // Multiplication
    // -------------------------------------------------------------------------

    public function test_mul_uses_intermediate_scale_by_default(): void
    {
        $result = DecimalHelper::mul('1.5000', '2.0000');
        $this->assertSame('3.00000000', $result);
    }

    public function test_mul_precision(): void
    {
        $result = DecimalHelper::mul('0.1', '0.1', 8);
        $this->assertSame('0.01000000', $result);
    }

    // -------------------------------------------------------------------------
    // Division
    // -------------------------------------------------------------------------

    public function test_div_basic(): void
    {
        $result = DecimalHelper::div('10.00000000', '4.00000000');
        $this->assertSame('2.50000000', $result);
    }

    public function test_div_by_zero_throws(): void
    {
        $this->expectException(\DivisionByZeroError::class);
        DecimalHelper::div('10', '0');
    }

    public function test_div_recurring_decimal(): void
    {
        $result = DecimalHelper::div('1', '3', 8);
        $this->assertSame('0.33333333', $result);
    }

    // -------------------------------------------------------------------------
    // Rounding
    // -------------------------------------------------------------------------

    public function test_round_to_monetary(): void
    {
        $result = DecimalHelper::round('1.005', 2);
        $this->assertSame('1.01', $result);
    }

    public function test_to_monetary(): void
    {
        $result = DecimalHelper::toMonetary('99.9999');
        $this->assertSame('100.00', $result);
    }

    public function test_to_monetary_negative(): void
    {
        $result = DecimalHelper::toMonetary('-1.555');
        $this->assertSame('-1.56', $result);
    }

    // -------------------------------------------------------------------------
    // Comparison
    // -------------------------------------------------------------------------

    public function test_compare_equal(): void
    {
        $this->assertSame(0, DecimalHelper::compare('1.0000', '1.0000'));
    }

    public function test_compare_greater(): void
    {
        $this->assertSame(1, DecimalHelper::compare('2.0000', '1.0000'));
    }

    public function test_compare_less(): void
    {
        $this->assertSame(-1, DecimalHelper::compare('1.0000', '2.0000'));
    }

    public function test_equals_returns_true(): void
    {
        $this->assertTrue(DecimalHelper::equals('5.0000', '5.0000'));
    }

    public function test_greater_than(): void
    {
        $this->assertTrue(DecimalHelper::greaterThan('10.0000', '9.9999'));
    }

    public function test_less_than(): void
    {
        $this->assertTrue(DecimalHelper::lessThan('0.0001', '0.0002'));
    }

    // -------------------------------------------------------------------------
    // Input validation
    // -------------------------------------------------------------------------

    public function test_add_throws_on_non_numeric_input(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DecimalHelper::add('abc', '1.0000');
    }

    public function test_div_throws_on_non_numeric_input(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DecimalHelper::div('10', 'x');
    }

    // -------------------------------------------------------------------------
    // Absolute value
    // -------------------------------------------------------------------------

    public function test_abs_positive(): void
    {
        $this->assertSame('5.0000', DecimalHelper::abs('5.0000'));
    }

    public function test_abs_negative(): void
    {
        $this->assertSame('5.0000', DecimalHelper::abs('-5.0000'));
    }

    // -------------------------------------------------------------------------
    // Power
    // -------------------------------------------------------------------------

    public function test_pow(): void
    {
        $result = DecimalHelper::pow('2', 10);
        $this->assertSame('1024.0000', $result);
    }

    // -------------------------------------------------------------------------
    // Modulo
    // -------------------------------------------------------------------------

    public function test_mod_basic(): void
    {
        $result = DecimalHelper::mod('10.0000', '3.0000');
        $this->assertSame('1.0000', $result);
    }

    public function test_mod_by_zero_throws(): void
    {
        $this->expectException(\DivisionByZeroError::class);
        DecimalHelper::mod('10', '0');
    }

    public function test_mod_throws_on_non_numeric_input(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DecimalHelper::mod('abc', '3');
    }

    // -------------------------------------------------------------------------
    // Integer conversion
    // -------------------------------------------------------------------------

    public function test_from_int_zero(): void
    {
        $this->assertSame('0', DecimalHelper::fromInt(0));
    }

    public function test_from_int_positive(): void
    {
        $this->assertSame('42', DecimalHelper::fromInt(42));
    }

    public function test_from_int_negative(): void
    {
        $this->assertSame('-7', DecimalHelper::fromInt(-7));
    }

    // -------------------------------------------------------------------------
    // greaterThanOrEqual / lessThanOrEqual
    // -------------------------------------------------------------------------

    public function test_greater_than_or_equal_when_equal(): void
    {
        $this->assertTrue(DecimalHelper::greaterThanOrEqual('5.0000', '5.0000'));
    }

    public function test_greater_than_or_equal_when_greater(): void
    {
        $this->assertTrue(DecimalHelper::greaterThanOrEqual('5.0001', '5.0000'));
    }

    public function test_greater_than_or_equal_when_less_returns_false(): void
    {
        $this->assertFalse(DecimalHelper::greaterThanOrEqual('4.9999', '5.0000'));
    }

    public function test_less_than_or_equal_when_equal(): void
    {
        $this->assertTrue(DecimalHelper::lessThanOrEqual('5.0000', '5.0000'));
    }

    public function test_less_than_or_equal_when_less(): void
    {
        $this->assertTrue(DecimalHelper::lessThanOrEqual('4.9999', '5.0000'));
    }

    public function test_less_than_or_equal_when_greater_returns_false(): void
    {
        $this->assertFalse(DecimalHelper::lessThanOrEqual('5.0001', '5.0000'));
    }

    // -------------------------------------------------------------------------
    // equals returns false
    // -------------------------------------------------------------------------

    public function test_equals_returns_false_for_different_values(): void
    {
        $this->assertFalse(DecimalHelper::equals('1.0000', '1.0001'));
    }

    // -------------------------------------------------------------------------
    // abs edge cases
    // -------------------------------------------------------------------------

    public function test_abs_zero(): void
    {
        $this->assertSame('0', DecimalHelper::abs('0'));
    }

    // -------------------------------------------------------------------------
    // round with zero scale
    // -------------------------------------------------------------------------

    public function test_round_with_zero_scale(): void
    {
        $this->assertSame('10', DecimalHelper::round('9.5000', 0));
    }

    // -------------------------------------------------------------------------
    // Input validation for mul and sub
    // -------------------------------------------------------------------------

    public function test_mul_throws_on_non_numeric_input(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DecimalHelper::mul('abc', '2');
    }

    public function test_sub_throws_on_non_numeric_input(): void
    {
        $this->expectException(InvalidArgumentException::class);
        DecimalHelper::sub('10', 'xyz');
    }

    // -------------------------------------------------------------------------
    // greaterThan / lessThan — false paths
    // -------------------------------------------------------------------------

    public function test_greater_than_returns_false_when_equal(): void
    {
        $this->assertFalse(DecimalHelper::greaterThan('5.0000', '5.0000'));
    }

    public function test_less_than_returns_false_when_equal(): void
    {
        $this->assertFalse(DecimalHelper::lessThan('5.0000', '5.0000'));
    }

    // -------------------------------------------------------------------------
    // Financial determinism guarantee
    // -------------------------------------------------------------------------

    public function test_add_is_deterministic(): void
    {
        $a = DecimalHelper::add('0.1000', '0.2000');
        $b = DecimalHelper::add('0.1000', '0.2000');
        $this->assertSame($a, $b);
        $this->assertSame('0.3000', $a);
    }

    public function test_no_floating_point_drift(): void
    {
        // Classic float problem: 0.1 + 0.2 != 0.3 in IEEE 754
        // BCMath must give exact result
        $result = DecimalHelper::add('0.1000', '0.2000', 4);
        $this->assertSame('0.3000', $result);
    }
}
