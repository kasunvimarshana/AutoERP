<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Unit;

use Modules\Auth\Application\DTOs\LoginDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for LoginDTO.
 *
 * Pure PHP — no database or Laravel bootstrap required.
 */
class LoginDTOTest extends TestCase
{
    public function test_from_array_hydrates_required_fields(): void
    {
        $dto = LoginDTO::fromArray([
            'email'    => 'user@example.com',
            'password' => 's3cr3t!',
        ]);

        $this->assertSame('user@example.com', $dto->email);
        $this->assertSame('s3cr3t!', $dto->password);
        $this->assertNull($dto->deviceName);
    }

    public function test_from_array_hydrates_optional_device_name(): void
    {
        $dto = LoginDTO::fromArray([
            'email'       => 'admin@example.com',
            'password'    => 'password123',
            'device_name' => 'iPhone 15',
        ]);

        $this->assertSame('iPhone 15', $dto->deviceName);
    }

    public function test_device_name_defaults_to_null(): void
    {
        $dto = LoginDTO::fromArray([
            'email'    => 'test@example.com',
            'password' => 'password',
        ]);

        $this->assertNull($dto->deviceName);
    }

    public function test_to_array_returns_correct_keys(): void
    {
        $dto = LoginDTO::fromArray([
            'email'       => 'user@example.com',
            'password'    => 'mypassword',
            'device_name' => 'Chrome',
        ]);

        $array = $dto->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('email', $array);
        $this->assertArrayHasKey('password', $array);
        $this->assertArrayHasKey('device_name', $array);

        $this->assertSame('user@example.com', $array['email']);
        $this->assertSame('mypassword', $array['password']);
        $this->assertSame('Chrome', $array['device_name']);
    }

    public function test_to_array_device_name_is_null_when_not_provided(): void
    {
        $dto = LoginDTO::fromArray([
            'email'    => 'anon@example.com',
            'password' => 'pass',
        ]);

        $array = $dto->toArray();

        $this->assertNull($array['device_name']);
    }

    public function test_email_is_a_string(): void
    {
        $dto = LoginDTO::fromArray([
            'email'    => 'email@domain.org',
            'password' => 'secure',
        ]);

        $this->assertIsString($dto->email);
    }

    public function test_password_is_a_string(): void
    {
        $dto = LoginDTO::fromArray([
            'email'    => 'user@domain.com',
            'password' => 'superSecret99',
        ]);

        $this->assertIsString($dto->password);
    }
}
