<?php

namespace Tests\Unit\ValueObjects;

use App\ValueObjects\Email;
use InvalidArgumentException;
use Tests\TestCase;

class EmailTest extends TestCase
{
    public function test_can_create_valid_email(): void
    {
        $email = Email::fromString('test@example.com');
        
        $this->assertEquals('test@example.com', $email->getValue());
    }

    public function test_email_is_normalized_to_lowercase(): void
    {
        $email = Email::fromString('Test@Example.COM');
        
        $this->assertEquals('test@example.com', $email->getValue());
    }

    public function test_email_is_trimmed(): void
    {
        $email = Email::fromString('  test@example.com  ');
        
        $this->assertEquals('test@example.com', $email->getValue());
    }

    public function test_cannot_create_invalid_email(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Email::fromString('invalid-email');
    }

    public function test_can_get_domain(): void
    {
        $email = Email::fromString('user@example.com');
        
        $this->assertEquals('example.com', $email->getDomain());
    }

    public function test_can_get_local_part(): void
    {
        $email = Email::fromString('user@example.com');
        
        $this->assertEquals('user', $email->getLocalPart());
    }

    public function test_can_compare_emails(): void
    {
        $email1 = Email::fromString('test@example.com');
        $email2 = Email::fromString('test@example.com');
        $email3 = Email::fromString('other@example.com');
        
        $this->assertTrue($email1->equals($email2));
        $this->assertFalse($email1->equals($email3));
    }

    public function test_can_convert_to_string(): void
    {
        $email = Email::fromString('test@example.com');
        
        $this->assertEquals('test@example.com', (string) $email);
    }

    public function test_can_json_serialize(): void
    {
        $email = Email::fromString('test@example.com');
        
        $this->assertEquals('test@example.com', $email->jsonSerialize());
    }
}
