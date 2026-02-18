<?php

namespace Tests\Unit\ValueObjects;

use App\ValueObjects\Address;
use InvalidArgumentException;
use Tests\TestCase;

class AddressTest extends TestCase
{
    public function test_can_create_valid_address(): void
    {
        $address = Address::create(
            '123 Main St',
            'New York',
            'NY',
            '10001',
            'USA'
        );
        
        $this->assertEquals('123 Main St', $address->getStreet());
        $this->assertEquals('New York', $address->getCity());
        $this->assertEquals('NY', $address->getState());
        $this->assertEquals('10001', $address->getPostalCode());
        $this->assertEquals('USA', $address->getCountry());
    }

    public function test_cannot_create_address_without_required_fields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        Address::create('', 'New York', 'NY', '10001', 'USA');
    }

    public function test_can_format_address(): void
    {
        $address = Address::create(
            '123 Main St',
            'New York',
            'NY',
            '10001',
            'USA'
        );
        
        $formatted = $address->format();
        
        $this->assertStringContainsString('123 Main St', $formatted);
        $this->assertStringContainsString('New York', $formatted);
        $this->assertStringContainsString('USA', $formatted);
    }

    public function test_can_compare_addresses(): void
    {
        $address1 = Address::create('123 Main St', 'NYC', 'NY', '10001', 'USA');
        $address2 = Address::create('123 Main St', 'NYC', 'NY', '10001', 'USA');
        $address3 = Address::create('456 Oak Ave', 'LA', 'CA', '90001', 'USA');
        
        $this->assertTrue($address1->equals($address2));
        $this->assertFalse($address1->equals($address3));
    }

    public function test_can_convert_to_string(): void
    {
        $address = Address::create('123 Main St', 'NYC', 'NY', '10001', 'USA');
        
        $string = (string) $address;
        
        $this->assertStringContainsString('123 Main St', $string);
    }

    public function test_can_json_serialize(): void
    {
        $address = Address::create('123 Main St', 'NYC', 'NY', '10001', 'USA');
        
        $json = $address->jsonSerialize();
        
        $this->assertIsArray($json);
        $this->assertEquals('123 Main St', $json['street']);
        $this->assertEquals('NYC', $json['city']);
        $this->assertArrayHasKey('formatted', $json);
    }
}
