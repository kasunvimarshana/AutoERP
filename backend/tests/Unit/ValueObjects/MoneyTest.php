<?php

namespace Tests\Unit\ValueObjects;

use App\ValueObjects\Money;
use InvalidArgumentException;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    public function test_can_create_money_with_amount_and_currency(): void
    {
        $money = Money::fromAmount(100.50, 'USD');
        
        $this->assertEquals(100.50, $money->getAmount());
        $this->assertEquals('USD', $money->getCurrency());
    }

    public function test_can_create_zero_money(): void
    {
        $money = Money::zero('EUR');
        
        $this->assertEquals(0.0, $money->getAmount());
        $this->assertEquals('EUR', $money->getCurrency());
        $this->assertTrue($money->isZero());
    }

    public function test_cannot_create_negative_money(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::fromAmount(-10, 'USD');
    }

    public function test_can_add_money(): void
    {
        $money1 = Money::fromAmount(100, 'USD');
        $money2 = Money::fromAmount(50, 'USD');
        
        $result = $money1->add($money2);
        
        $this->assertEquals(150, $result->getAmount());
    }

    public function test_cannot_add_different_currencies(): void
    {
        $money1 = Money::fromAmount(100, 'USD');
        $money2 = Money::fromAmount(50, 'EUR');
        
        $this->expectException(InvalidArgumentException::class);
        $money1->add($money2);
    }

    public function test_can_subtract_money(): void
    {
        $money1 = Money::fromAmount(100, 'USD');
        $money2 = Money::fromAmount(30, 'USD');
        
        $result = $money1->subtract($money2);
        
        $this->assertEquals(70, $result->getAmount());
    }

    public function test_cannot_subtract_more_than_amount(): void
    {
        $money1 = Money::fromAmount(50, 'USD');
        $money2 = Money::fromAmount(100, 'USD');
        
        $this->expectException(InvalidArgumentException::class);
        $money1->subtract($money2);
    }

    public function test_can_multiply_money(): void
    {
        $money = Money::fromAmount(10, 'USD');
        
        $result = $money->multiply(2.5);
        
        $this->assertEquals(25, $result->getAmount());
    }

    public function test_can_divide_money(): void
    {
        $money = Money::fromAmount(100, 'USD');
        
        $result = $money->divide(4);
        
        $this->assertEquals(25, $result->getAmount());
    }

    public function test_can_compare_money(): void
    {
        $money1 = Money::fromAmount(100, 'USD');
        $money2 = Money::fromAmount(50, 'USD');
        $money3 = Money::fromAmount(100, 'USD');
        
        $this->assertTrue($money1->isGreaterThan($money2));
        $this->assertTrue($money2->isLessThan($money1));
        $this->assertTrue($money1->equals($money3));
    }

    public function test_can_format_money(): void
    {
        $money = Money::fromAmount(100.50, 'USD');
        
        $this->assertEquals('USD 100.50', $money->format());
        $this->assertEquals('USD 100.50', (string) $money);
    }

    public function test_can_json_serialize(): void
    {
        $money = Money::fromAmount(100.50, 'USD');
        
        $json = $money->jsonSerialize();
        
        $this->assertIsArray($json);
        $this->assertEquals(100.50, $json['amount']);
        $this->assertEquals('USD', $json['currency']);
        $this->assertEquals('USD 100.50', $json['formatted']);
    }

    public function test_currency_must_be_three_letters(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::fromAmount(100, 'US');
    }
}
