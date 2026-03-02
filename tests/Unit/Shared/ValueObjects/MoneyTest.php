<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\ValueObjects;

use App\Shared\ValueObjects\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_can_create_money(): void
    {
        $money = new Money('100.0000', 'USD');
        $this->assertSame('100.0000', $money->amount);
        $this->assertSame('USD', $money->currency);
    }

    public function test_can_add_money(): void
    {
        $a = new Money('100.5000', 'USD');
        $b = new Money('50.2500', 'USD');
        $result = $a->add($b);
        $this->assertSame('150.7500', $result->amount);
    }

    public function test_can_subtract_money(): void
    {
        $a = new Money('100.0000', 'USD');
        $b = new Money('30.0000', 'USD');
        $result = $a->subtract($b);
        $this->assertSame('70.0000', $result->amount);
    }

    public function test_can_multiply_money(): void
    {
        $price = new Money('25.0000', 'USD');
        $result = $price->multiply('3');
        $this->assertSame('75.0000', $result->amount);
    }

    public function test_throws_on_currency_mismatch(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $usd = new Money('100.0000', 'USD');
        $eur = new Money('50.0000', 'EUR');
        $usd->add($eur);
    }

    public function test_throws_on_invalid_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Money('not-a-number', 'USD');
    }

    public function test_display_rounds_to_two_decimals(): void
    {
        $money = new Money('1234.5678', 'USD');
        $this->assertSame('1,234.57', $money->toDisplay());
    }
}
