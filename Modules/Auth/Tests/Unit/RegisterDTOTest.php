<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Unit;

use Modules\Auth\Application\DTOs\RegisterDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RegisterDTO.
 *
 * Pure PHP — no database or Laravel bootstrap required.
 */
class RegisterDTOTest extends TestCase
{
    public function test_from_array_hydrates_required_fields(): void
    {
        $dto = RegisterDTO::fromArray([
            'tenant_id' => 1,
            'name'      => 'Alice Smith',
            'email'     => 'alice@example.com',
            'password'  => 's3cr3t!',
        ]);

        $this->assertSame(1, $dto->tenantId);
        $this->assertSame('Alice Smith', $dto->name);
        $this->assertSame('alice@example.com', $dto->email);
        $this->assertSame('s3cr3t!', $dto->password);
        $this->assertNull($dto->deviceName);
    }

    public function test_from_array_hydrates_optional_device_name(): void
    {
        $dto = RegisterDTO::fromArray([
            'tenant_id'   => 2,
            'name'        => 'Bob Jones',
            'email'       => 'bob@example.com',
            'password'    => 'password123',
            'device_name' => 'Android Phone',
        ]);

        $this->assertSame('Android Phone', $dto->deviceName);
    }

    public function test_device_name_defaults_to_null(): void
    {
        $dto = RegisterDTO::fromArray([
            'tenant_id' => 3,
            'name'      => 'Carol White',
            'email'     => 'carol@example.com',
            'password'  => 'pass1234',
        ]);

        $this->assertNull($dto->deviceName);
    }

    public function test_tenant_id_is_cast_to_int(): void
    {
        $dto = RegisterDTO::fromArray([
            'tenant_id' => '5',
            'name'      => 'Dave Brown',
            'email'     => 'dave@example.com',
            'password'  => 'pass5678',
        ]);

        $this->assertIsInt($dto->tenantId);
        $this->assertSame(5, $dto->tenantId);
    }

    public function test_to_array_returns_correct_keys(): void
    {
        $dto = RegisterDTO::fromArray([
            'tenant_id'   => 10,
            'name'        => 'Eve Green',
            'email'       => 'eve@example.com',
            'password'    => 'mypassword',
            'device_name' => 'Safari',
        ]);

        $array = $dto->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('tenant_id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('email', $array);
        $this->assertArrayHasKey('password', $array);
        $this->assertArrayHasKey('device_name', $array);

        $this->assertSame(10, $array['tenant_id']);
        $this->assertSame('Eve Green', $array['name']);
        $this->assertSame('eve@example.com', $array['email']);
        $this->assertSame('mypassword', $array['password']);
        $this->assertSame('Safari', $array['device_name']);
    }

    public function test_to_array_device_name_is_null_when_not_provided(): void
    {
        $dto = RegisterDTO::fromArray([
            'tenant_id' => 1,
            'name'      => 'Anon User',
            'email'     => 'anon@example.com',
            'password'  => 'pass',
        ]);

        $this->assertNull($dto->toArray()['device_name']);
    }

    public function test_name_is_a_string(): void
    {
        $dto = RegisterDTO::fromArray([
            'tenant_id' => 1,
            'name'      => 'Frank Black',
            'email'     => 'frank@example.com',
            'password'  => 'secret99',
        ]);

        $this->assertIsString($dto->name);
    }

    public function test_email_is_a_string(): void
    {
        $dto = RegisterDTO::fromArray([
            'tenant_id' => 1,
            'name'      => 'Grace Hall',
            'email'     => 'grace@example.com',
            'password'  => 'secure!',
        ]);

        $this->assertIsString($dto->email);
    }
}
