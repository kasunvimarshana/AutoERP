<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Services\JwtTokenService;
use Tests\TestCase;

/**
 * Test JWT Token Service
 */
class JwtTokenServiceTest extends TestCase
{
    use RefreshDatabase;

    private JwtTokenService $jwtService;

    protected function setUp(): void
    {
        parent::setUp();

        // Run migrations for testing
        $this->artisan('migrate', ['--path' => 'modules/Auth/Database/Migrations']);

        // Set JWT configuration for testing - config returns proper types from jwt.php
        $this->jwtService = new JwtTokenService;
    }

    /**
     * Test token generation
     */
    public function test_can_generate_jwt_token(): void
    {
        $userId = 'test-user-id-123';
        $deviceId = 'test-device-id-456';
        $tenantId = 'test-tenant-id-789';
        $organizationId = 'test-org-id-012';

        $token = $this->jwtService->generate($userId, $deviceId, $organizationId, $tenantId);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        // JWT tokens have 3 parts separated by dots
        $this->assertCount(3, explode('.', $token));
    }

    /**
     * Test token validation
     */
    public function test_can_validate_valid_token(): void
    {
        $userId = 'test-user-id-123';
        $deviceId = 'test-device-id-456';
        $tenantId = 'test-tenant-id-789';
        $organizationId = 'test-org-id-012';

        $token = $this->jwtService->generate($userId, $deviceId, $organizationId, $tenantId);
        $payload = $this->jwtService->validate($token);

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('sub', $payload);
        $this->assertArrayHasKey('device_id', $payload);
        $this->assertArrayHasKey('tenant_id', $payload);
        $this->assertArrayHasKey('organization_id', $payload);
        $this->assertEquals($userId, $payload['sub']);
        $this->assertEquals($deviceId, $payload['device_id']);
        $this->assertEquals($tenantId, $payload['tenant_id']);
        $this->assertEquals($organizationId, $payload['organization_id']);
    }

    /**
     * Test token refresh
     */
    public function test_can_refresh_token(): void
    {
        $userId = 'test-user-id-123';
        $deviceId = 'test-device-id-456';
        $tenantId = 'test-tenant-id-789';
        $organizationId = 'test-org-id-012';

        $token = $this->jwtService->generate($userId, $deviceId, $organizationId, $tenantId);

        // Sleep a second to ensure new timestamp
        sleep(1);

        $newToken = $this->jwtService->refresh($token);

        $this->assertIsString($newToken);
        $this->assertNotEmpty($newToken);
        $this->assertNotEquals($token, $newToken);

        // Validate new token has same claims
        $payload = $this->jwtService->validate($newToken);
        $this->assertEquals($userId, $payload['sub']);
        $this->assertEquals($deviceId, $payload['device_id']);
    }

    /**
     * Test invalid token throws exception
     */
    public function test_invalid_token_throws_exception(): void
    {
        $this->expectException(\Modules\Auth\Exceptions\TokenInvalidException::class);

        $this->jwtService->validate('invalid.token.here');
    }

    /**
     * Test malformed token throws exception
     */
    public function test_malformed_token_throws_exception(): void
    {
        $this->expectException(\Modules\Auth\Exceptions\TokenInvalidException::class);

        $this->jwtService->validate('not-a-valid-jwt');
    }

    /**
     * Test token claims extraction
     */
    public function test_can_extract_claims_from_token(): void
    {
        $userId = 'user-123';
        $deviceId = 'device-456';
        $tenantId = 'tenant-789';
        $organizationId = 'org-012';

        $token = $this->jwtService->generate($userId, $deviceId, $organizationId, $tenantId);
        $claims = $this->jwtService->getClaims($token);

        $this->assertIsArray($claims);
        $this->assertArrayHasKey('sub', $claims);
        $this->assertArrayHasKey('iat', $claims);
        $this->assertArrayHasKey('exp', $claims);
        $this->assertArrayHasKey('iss', $claims);
        $this->assertArrayHasKey('device_id', $claims);
        $this->assertArrayHasKey('tenant_id', $claims);
        $this->assertArrayHasKey('organization_id', $claims);
    }

    /**
     * Test token expiration time is set correctly
     */
    public function test_token_expiration_is_set(): void
    {
        $userId = 'user-123';
        $deviceId = 'device-456';
        $tenantId = 'tenant-789';
        $organizationId = 'org-012';

        $token = $this->jwtService->generate($userId, $deviceId, $organizationId, $tenantId);
        $claims = $this->jwtService->getClaims($token);

        $this->assertArrayHasKey('exp', $claims);
        $this->assertIsInt($claims['exp']);
        $this->assertGreaterThan(time(), $claims['exp']);

        // Should expire in approximately 1 hour (with some tolerance)
        $expectedExpiration = time() + 3600;
        $this->assertLessThan(60, abs($claims['exp'] - $expectedExpiration));
    }
}
