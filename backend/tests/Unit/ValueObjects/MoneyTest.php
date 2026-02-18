<?php

namespace Tests\Unit\ValueObjects;

use App\ValueObjects\Money;
<<<<<<< HEAD
use App\Services\Finance\CurrencyService;
=======
>>>>>>> kv-erp-001
use InvalidArgumentException;
use Tests\TestCase;

class MoneyTest extends TestCase
{
<<<<<<< HEAD
    public function test_can_create_money_object()
    {
        $money = new Money(100.50, 'USD');
=======
    public function test_can_create_money_with_amount_and_currency(): void
    {
        $money = Money::fromAmount(100.50, 'USD');
>>>>>>> kv-erp-001
        
        $this->assertEquals(100.50, $money->getAmount());
        $this->assertEquals('USD', $money->getCurrency());
    }

<<<<<<< HEAD
    public function test_cannot_create_money_with_negative_amount()
    {
        $this->expectException(InvalidArgumentException::class);
        new Money(-100, 'USD');
    }

    public function test_can_add_money()
    {
        $money1 = new Money(100, 'USD');
        $money2 = new Money(50, 'USD');
=======
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
>>>>>>> kv-erp-001
        
        $result = $money1->add($money2);
        
        $this->assertEquals(150, $result->getAmount());
<<<<<<< HEAD
        $this->assertEquals('USD', $result->getCurrency());
    }

    public function test_cannot_add_money_with_different_currencies()
    {
        $money1 = new Money(100, 'USD');
        $money2 = new Money(50, 'EUR');
=======
    }

    public function test_cannot_add_different_currencies(): void
    {
        $money1 = Money::fromAmount(100, 'USD');
        $money2 = Money::fromAmount(50, 'EUR');
>>>>>>> kv-erp-001
        
        $this->expectException(InvalidArgumentException::class);
        $money1->add($money2);
    }

<<<<<<< HEAD
    public function test_can_subtract_money()
    {
        $money1 = new Money(100, 'USD');
        $money2 = new Money(30, 'USD');
=======
    public function test_can_subtract_money(): void
    {
        $money1 = Money::fromAmount(100, 'USD');
        $money2 = Money::fromAmount(30, 'USD');
>>>>>>> kv-erp-001
        
        $result = $money1->subtract($money2);
        
        $this->assertEquals(70, $result->getAmount());
<<<<<<< HEAD
        $this->assertEquals('USD', $result->getCurrency());
    }

    public function test_cannot_subtract_to_negative()
    {
        $money1 = new Money(50, 'USD');
        $money2 = new Money(100, 'USD');
=======
    }

    public function test_cannot_subtract_more_than_amount(): void
    {
        $money1 = Money::fromAmount(50, 'USD');
        $money2 = Money::fromAmount(100, 'USD');
>>>>>>> kv-erp-001
        
        $this->expectException(InvalidArgumentException::class);
        $money1->subtract($money2);
    }

<<<<<<< HEAD
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
=======
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
>>>>>>> kv-erp-001
    }
}
