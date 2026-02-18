<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Modules\IAM\Services\JwtService;

class JwtServiceTest extends TestCase
{
    use RefreshDatabase;

    private JwtService $jwtService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->jwtService = app(JwtService::class);
    }

    public function test_it_generates_jwt_tokens_for_user(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'tenant_id' => 1,
        ]);

        // Act
        $tokens = $this->jwtService->generateToken($user);

        // Assert
        $this->assertArrayHasKey('access_token', $tokens);
        $this->assertArrayHasKey('refresh_token', $tokens);
        $this->assertArrayHasKey('token_type', $tokens);
        $this->assertArrayHasKey('expires_in', $tokens);
        $this->assertArrayHasKey('tenant_id', $tokens);
        $this->assertEquals('Bearer', $tokens['token_type']);
        $this->assertEquals(1, $tokens['tenant_id']);
    }

    public function test_it_decodes_valid_jwt_token(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'test@example.com']);
        $tokens = $this->jwtService->generateToken($user);

        // Act
        $payload = $this->jwtService->decodeToken($tokens['access_token']);

        // Assert
        $this->assertNotNull($payload);
        $this->assertEquals($user->id, $payload['sub']);
        $this->assertEquals($user->email, $payload['email']);
        $this->assertArrayHasKey('iat', $payload);
        $this->assertArrayHasKey('exp', $payload);
    }

    public function test_it_rejects_invalid_jwt_token(): void
    {
        // Act
        $payload = $this->jwtService->decodeToken('invalid.token.here');

        // Assert
        $this->assertNull($payload);
    }

    public function test_it_refreshes_access_token_with_valid_refresh_token(): void
    {
        // Arrange
        $user = User::factory()->create([
            'is_active' => true,
        ]);
        $tokens = $this->jwtService->generateToken($user);

        // Act
        $newTokens = $this->jwtService->refreshToken($tokens['refresh_token']);

        // Assert
        $this->assertNotNull($newTokens);
        $this->assertArrayHasKey('access_token', $newTokens);
        $this->assertNotEquals($tokens['access_token'], $newTokens['access_token']);
    }

    public function test_it_revokes_token_successfully(): void
    {
        // Arrange
        $user = User::factory()->create();
        $tokens = $this->jwtService->generateToken($user);

        // Act
        $result = $this->jwtService->revokeToken($tokens['access_token']);

        // Assert
        $this->assertTrue($result);
        
        // Verify token is blacklisted
        $payload = $this->jwtService->decodeToken($tokens['access_token']);
        $this->assertNull($payload);
    }

    public function test_it_revokes_all_user_tokens(): void
    {
        // Arrange
        $user = User::factory()->create();
        $tokens1 = $this->jwtService->generateToken($user);
        $tokens2 = $this->jwtService->generateToken($user);

        // Act
        $this->jwtService->revokeAllUserTokens($user->id);

        // Assert
        $payload1 = $this->jwtService->decodeToken($tokens1['access_token']);
        $payload2 = $this->jwtService->decodeToken($tokens2['access_token']);
        
        $this->assertNull($payload1);
        $this->assertNull($payload2);
    }

    public function test_it_generates_tokens_with_custom_tenant_id(): void
    {
        // Arrange
        $user = User::factory()->create(['tenant_id' => 1]);

        // Act
        $tokens = $this->jwtService->generateToken($user, 2);

        // Assert
        $this->assertEquals(2, $tokens['tenant_id']);
        
        $payload = $this->jwtService->decodeToken($tokens['access_token']);
        $this->assertEquals(2, $payload['tenant_id']);
    }
}
