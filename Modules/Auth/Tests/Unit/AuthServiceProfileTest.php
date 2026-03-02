<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Unit;

use Modules\Auth\Application\Services\AuthService;
use Modules\Auth\Domain\Entities\User;
use PHPUnit\Framework\TestCase;

/**
 * Structural tests for AuthService::updateProfile.
 *
 * updateProfile() relies on auth() facade and DB::transaction(),
 * which require a full Laravel bootstrap. These tests validate
 * method existence, signature, visibility, and return type only.
 */
class AuthServiceProfileTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Method existence
    // -------------------------------------------------------------------------

    public function test_auth_service_has_update_profile_method(): void
    {
        $this->assertTrue(
            method_exists(AuthService::class, 'updateProfile'),
            'AuthService must expose a public updateProfile() method.'
        );
    }

    // -------------------------------------------------------------------------
    // Visibility
    // -------------------------------------------------------------------------

    public function test_update_profile_is_public(): void
    {
        $reflection = new \ReflectionMethod(AuthService::class, 'updateProfile');

        $this->assertTrue($reflection->isPublic());
    }

    // -------------------------------------------------------------------------
    // Parameter signature
    // -------------------------------------------------------------------------

    public function test_update_profile_accepts_data_array(): void
    {
        $reflection = new \ReflectionMethod(AuthService::class, 'updateProfile');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('data', $params[0]->getName());
        $this->assertSame('array', (string) $params[0]->getType());
    }

    // -------------------------------------------------------------------------
    // Return type
    // -------------------------------------------------------------------------

    public function test_update_profile_return_type_is_user(): void
    {
        $reflection = new \ReflectionMethod(AuthService::class, 'updateProfile');
        $returnType = (string) $reflection->getReturnType();

        $this->assertSame(User::class, $returnType);
    }

    // -------------------------------------------------------------------------
    // Related method existence checks
    // -------------------------------------------------------------------------

    public function test_auth_service_still_has_me_method(): void
    {
        $this->assertTrue(
            method_exists(AuthService::class, 'me'),
            'AuthService must still expose the me() method.'
        );
    }

    public function test_auth_service_still_has_login_method(): void
    {
        $this->assertTrue(
            method_exists(AuthService::class, 'login'),
            'AuthService must still expose the login() method.'
        );
    }

    // -------------------------------------------------------------------------
    // Service instantiation
    // -------------------------------------------------------------------------

    public function test_auth_service_can_be_instantiated(): void
    {
        $service = new AuthService();

        $this->assertInstanceOf(AuthService::class, $service);
    }

    public function test_update_profile_is_not_static(): void
    {
        $reflection = new \ReflectionMethod(AuthService::class, 'updateProfile');

        $this->assertFalse($reflection->isStatic());
    }
}
