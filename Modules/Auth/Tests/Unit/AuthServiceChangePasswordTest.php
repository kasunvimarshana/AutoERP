<?php

declare(strict_types=1);

namespace Modules\Auth\Tests\Unit;

use Modules\Auth\Application\Services\AuthService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Structural tests for AuthService::changePassword().
 *
 * changePassword() relies on auth() and Hash facades and DB::transaction().
 * These tests validate method existence, signature, visibility, and return type only.
 */
class AuthServiceChangePasswordTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Method existence
    // -------------------------------------------------------------------------

    public function test_auth_service_has_change_password_method(): void
    {
        $this->assertTrue(
            method_exists(AuthService::class, 'changePassword'),
            'AuthService must expose a public changePassword() method.'
        );
    }

    // -------------------------------------------------------------------------
    // Visibility
    // -------------------------------------------------------------------------

    public function test_change_password_is_public(): void
    {
        $ref = new ReflectionMethod(AuthService::class, 'changePassword');
        $this->assertTrue($ref->isPublic());
    }

    // -------------------------------------------------------------------------
    // Parameter signature
    // -------------------------------------------------------------------------

    public function test_change_password_accepts_current_password_string(): void
    {
        $ref    = new ReflectionMethod(AuthService::class, 'changePassword');
        $params = $ref->getParameters();

        $this->assertGreaterThanOrEqual(2, count($params));
        $this->assertSame('currentPassword', $params[0]->getName());
        $this->assertSame('string', (string) $params[0]->getType());
    }

    public function test_change_password_accepts_new_password_string(): void
    {
        $ref    = new ReflectionMethod(AuthService::class, 'changePassword');
        $params = $ref->getParameters();

        $this->assertSame('newPassword', $params[1]->getName());
        $this->assertSame('string', (string) $params[1]->getType());
    }

    // -------------------------------------------------------------------------
    // Return type
    // -------------------------------------------------------------------------

    public function test_change_password_return_type_is_void(): void
    {
        $ref = new ReflectionMethod(AuthService::class, 'changePassword');
        $this->assertSame('void', (string) $ref->getReturnType());
    }

    // -------------------------------------------------------------------------
    // Not static
    // -------------------------------------------------------------------------

    public function test_change_password_is_not_static(): void
    {
        $ref = new ReflectionMethod(AuthService::class, 'changePassword');
        $this->assertFalse($ref->isStatic());
    }

    // -------------------------------------------------------------------------
    // Service instantiation
    // -------------------------------------------------------------------------

    public function test_auth_service_can_be_instantiated(): void
    {
        $service = new AuthService();
        $this->assertInstanceOf(AuthService::class, $service);
    }

    // -------------------------------------------------------------------------
    // Sibling method still present
    // -------------------------------------------------------------------------

    public function test_update_profile_still_exists(): void
    {
        $this->assertTrue(
            method_exists(AuthService::class, 'updateProfile'),
            'updateProfile() must still exist after adding changePassword().'
        );
    }
}
