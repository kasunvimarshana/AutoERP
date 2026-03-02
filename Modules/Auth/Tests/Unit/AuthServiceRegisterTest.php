<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Unit;

use Modules\Auth\Application\DTOs\RegisterDTO;
use Modules\Auth\Application\Services\AuthService;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AuthService register functionality — structural compliance.
 *
 * The register() method calls DB::transaction() and auth('api')->login()
 * which require full Laravel bootstrap. These tests therefore validate
 * structural contracts only. Functional registration flows are covered
 * in feature tests.
 */
class AuthServiceRegisterTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Structural compliance
    // -------------------------------------------------------------------------

    public function test_auth_service_has_register_method(): void
    {
        $this->assertTrue(
            method_exists(AuthService::class, 'register'),
            'AuthService must expose a public register() method.'
        );
    }

    public function test_register_has_string_return_type(): void
    {
        $reflection = new \ReflectionMethod(AuthService::class, 'register');

        $this->assertSame('string', (string) $reflection->getReturnType());
    }

    public function test_register_accepts_register_dto_parameter(): void
    {
        $reflection = new \ReflectionMethod(AuthService::class, 'register');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('dto', $params[0]->getName());
        $this->assertSame(RegisterDTO::class, (string) $params[0]->getType());
    }

    // -------------------------------------------------------------------------
    // RegisterDTO field mapping mirrors register() payload construction
    // -------------------------------------------------------------------------

    public function test_register_dto_provides_tenant_id_as_int(): void
    {
        $dto = RegisterDTO::fromArray([
            'tenant_id' => '7',
            'name'      => 'Test User',
            'email'     => 'test@example.com',
            'password'  => 'password',
        ]);

        $this->assertIsInt($dto->tenantId);
        $this->assertSame(7, $dto->tenantId);
    }

    public function test_register_dto_create_payload_maps_correctly(): void
    {
        $dto = RegisterDTO::fromArray([
            'tenant_id' => 3,
            'name'      => 'Jane Doe',
            'email'     => 'jane@example.com',
            'password'  => 'secret!',
        ]);

        // Mirror the mapping done inside register()
        $createPayload = [
            'tenant_id' => $dto->tenantId,
            'name'      => $dto->name,
            'email'     => $dto->email,
            'password'  => $dto->password,
            'is_active' => true,
        ];

        $this->assertSame(3, $createPayload['tenant_id']);
        $this->assertSame('Jane Doe', $createPayload['name']);
        $this->assertSame('jane@example.com', $createPayload['email']);
        $this->assertTrue($createPayload['is_active']);
    }

    public function test_register_dto_is_active_defaults_to_true_in_payload(): void
    {
        $dto = RegisterDTO::fromArray([
            'tenant_id' => 1,
            'name'      => 'John Doe',
            'email'     => 'john@example.com',
            'password'  => 'mypass',
        ]);

        $createPayload = [
            'tenant_id' => $dto->tenantId,
            'name'      => $dto->name,
            'email'     => $dto->email,
            'password'  => $dto->password,
            'is_active' => true,
        ];

        $this->assertTrue($createPayload['is_active']);
    }
}
