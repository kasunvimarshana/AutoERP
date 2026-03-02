<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Unit;

use Modules\Auth\Application\Services\AuthService;
use Modules\Core\Domain\Contracts\ServiceContract;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AuthService — structural and interface compliance.
 *
 * AuthService methods rely on JWTAuth facades and Laravel auth guards
 * which require a full application bootstrap. These tests therefore
 * validate structural contracts and class-level compliance only.
 * Functional login/refresh/logout flows are covered in feature tests.
 */
class AuthServiceTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Structural compliance
    // -------------------------------------------------------------------------

    public function test_auth_service_can_be_instantiated(): void
    {
        $service = new AuthService();

        $this->assertInstanceOf(AuthService::class, $service);
    }

    public function test_auth_service_implements_service_contract(): void
    {
        $service = new AuthService();

        $this->assertInstanceOf(ServiceContract::class, $service);
    }

    // -------------------------------------------------------------------------
    // Required method existence
    // -------------------------------------------------------------------------

    public function test_auth_service_has_login_method(): void
    {
        $this->assertTrue(
            method_exists(AuthService::class, 'login'),
            'AuthService must expose a public login() method.'
        );
    }

    public function test_auth_service_has_logout_method(): void
    {
        $this->assertTrue(
            method_exists(AuthService::class, 'logout'),
            'AuthService must expose a public logout() method.'
        );
    }

    public function test_auth_service_has_refresh_method(): void
    {
        $this->assertTrue(
            method_exists(AuthService::class, 'refresh'),
            'AuthService must expose a public refresh() method.'
        );
    }

    public function test_auth_service_has_me_method(): void
    {
        $this->assertTrue(
            method_exists(AuthService::class, 'me'),
            'AuthService must expose a public me() method.'
        );
    }

    // -------------------------------------------------------------------------
    // Return type declarations (reflection)
    // -------------------------------------------------------------------------

    public function test_login_has_string_return_type(): void
    {
        $reflection = new \ReflectionMethod(AuthService::class, 'login');

        $this->assertSame('string', (string) $reflection->getReturnType());
    }

    public function test_logout_has_void_return_type(): void
    {
        $reflection = new \ReflectionMethod(AuthService::class, 'logout');

        $this->assertSame('void', (string) $reflection->getReturnType());
    }

    public function test_refresh_has_string_return_type(): void
    {
        $reflection = new \ReflectionMethod(AuthService::class, 'refresh');

        $this->assertSame('string', (string) $reflection->getReturnType());
    }
}
