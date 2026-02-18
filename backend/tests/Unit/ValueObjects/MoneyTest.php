<?php

namespace Tests\Unit\ValueObjects;

use App\ValueObjects\Money;
use App\Services\Finance\CurrencyService;
use InvalidArgumentException;
use Tests\TestCase;

class MoneyTest extends TestCase
{
    public function test_can_create_money_object()
    {
        $money = new Money(100.50, 'USD');
        
        $this->assertEquals(100.50, $money->getAmount());
        $this->assertEquals('USD', $money->getCurrency());
    }

    public function test_cannot_create_money_with_negative_amount()
    {
        $this->expectException(InvalidArgumentException::class);
        new Money(-100, 'USD');
    }

    public function test_can_add_money()
    {
        $money1 = new Money(100, 'USD');
        $money2 = new Money(50, 'USD');
        
        $result = $money1->add($money2);
        
        $this->assertEquals(150, $result->getAmount());
        $this->assertEquals('USD', $result->getCurrency());
    }

    public function test_cannot_add_money_with_different_currencies()
    {
        $money1 = new Money(100, 'USD');
        $money2 = new Money(50, 'EUR');
        
        $this->expectException(InvalidArgumentException::class);
        $money1->add($money2);
    }

    public function test_can_subtract_money()
    {
        $money1 = new Money(100, 'USD');
        $money2 = new Money(30, 'USD');
        
        $result = $money1->subtract($money2);
        
        $this->assertEquals(70, $result->getAmount());
        $this->assertEquals('USD', $result->getCurrency());
    }

    public function test_cannot_subtract_to_negative()
    {
        $money1 = new Money(50, 'USD');
        $money2 = new Money(100, 'USD');
        
        $this->expectException(InvalidArgumentException::class);
        $money1->subtract($money2);
    }

    public function test_can_multiply_money()
    {
        $money = new Money(100, 'USD');
        
        $result = $money->multiply(2);
        
        $this->assertEquals(200, $result->getAmount());
        $this->assertEquals('USD', $result->getCurrency());
    }

    public function test_can_divide_money()
    {
        $money = new Money(100, 'USD');
        
        $result = $money->divide(2);
        
        $this->assertEquals(50, $result->getAmount());
        $this->assertEquals('USD', $result->getCurrency());
    }

    public function test_can_compare_money()
    {
        $money1 = new Money(100, 'USD');
        $money2 = new Money(50, 'USD');
        $money3 = new Money(100, 'USD');
        
        $this->assertTrue($money1->greaterThan($money2));
        $this->assertTrue($money2->lessThan($money1));
        $this->assertTrue($money1->equals($money3));
    }

    public function test_can_check_if_zero()
    {
        $money = new Money(0, 'USD');
        
        $this->assertTrue($money->isZero());
    }

    public function test_can_convert_to_array()
    {
        $money = new Money(100.50, 'USD');
        
        $array = $money->toArray();
        
        $this->assertEquals([
            'amount' => 100.50,
            'currency' => 'USD',
        ], $array);
    }

    public function test_can_create_from_array()
    {
        $money = Money::fromArray([
            'amount' => 100.50,
            'currency' => 'EUR',
        ]);
        
        $this->assertEquals(100.50, $money->getAmount());
        $this->assertEquals('EUR', $money->getCurrency());
    }
}
