<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use PHPUnit\Framework\TestCase;

final class DecimalMathTest extends TestCase
{
    private DecimalMath $math;

    protected function setUp(): void
    {
        parent::setUp();
        $this->math = new DecimalMath();
    }

    public function test_it_performs_exact_base_ten_arithmetic_without_float_conversion(): void
    {
        self::assertSame('0.300000', $this->math->add('0.100000', '0.200000'));
        self::assertSame('9.999999', $this->math->sub('10.000000', '0.000001'));
        self::assertSame('13132.875000', $this->math->mul('10.500000', '1250.750000'));
        self::assertSame('3.333333', $this->math->div('10', '3'));
    }

    public function test_arithmetic_does_not_truncate_operands_before_calculation(): void
    {
        self::assertSame('1000000.900000', $this->math->mul('1.0000009', '1000000'));
    }

    public function test_it_normalizes_plain_decimal_strings_and_negative_zero(): void
    {
        self::assertSame('12.340000', $this->math->normalize('00012.34'));
        self::assertSame('0.000000', $this->math->normalize('-0.000000'));
        self::assertSame('-12.340000', $this->math->normalize('-0012.34'));
        self::assertSame('12.340000', $this->math->normalize('12.3400000'));
    }

    public function test_it_rejects_silent_precision_loss(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->math->normalize('1.0000001');
    }

    public function test_it_rejects_scientific_notation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->math->normalize('1e3');
    }

    public function test_it_rejects_division_by_exact_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->math->div('1', '0.000000000000000000');
    }
}
