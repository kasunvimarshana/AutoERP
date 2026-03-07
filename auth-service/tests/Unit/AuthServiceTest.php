<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Tenant;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Unit tests for AuthService.
 */
final class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = $this->app->make(AuthService::class);
    }

    /** @test */
    public function it_registers_a_new_user_and_returns_a_token(): void
    {
        $tenant = Tenant::factory()->create();

        $result = $this->authService->register([
            'tenant_id' => $tenant->id,
            'name'      => 'Test User',
            'email'     => 'test@example.com',
            'password'  => 'password123',
        ]);

        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertInstanceOf(User::class, $result['user']);
        $this->assertNotEmpty($result['token']);
    }

    /** @test */
    public function it_returns_null_for_invalid_login_credentials(): void
    {
        $result = $this->authService->login([
            'email'    => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

        $this->assertNull($result);
    }

    /** @test */
    public function it_returns_null_when_inactive_user_attempts_login(): void
    {
        $tenant = Tenant::factory()->create();
        User::factory()->create([
            'tenant_id' => $tenant->id,
            'email'     => 'inactive@example.com',
            'password'  => Hash::make('password123'),
            'is_active' => false,
        ]);

        $result = $this->authService->login([
            'email'    => 'inactive@example.com',
            'password' => 'password123',
        ]);

        $this->assertNull($result);
    }

    /** @test */
    public function it_logs_in_an_active_user_and_returns_token_data(): void
    {
        $tenant = Tenant::factory()->create();
        User::factory()->create([
            'tenant_id' => $tenant->id,
            'email'     => 'active@example.com',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);

        $result = $this->authService->login([
            'email'    => 'active@example.com',
            'password' => 'password123',
        ]);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('token_type', $result);
        $this->assertArrayHasKey('expires_in', $result);
        $this->assertSame('Bearer', $result['token_type']);
    }

    /** @test */
    public function it_revokes_all_tokens_on_logout(): void
    {
        $tenant = Tenant::factory()->create();
        $user   = User::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);

        // Issue a token then immediately logout
        $user->createToken('Test Token');

        $this->assertSame(1, $user->tokens()->where('revoked', false)->count());

        $this->authService->logout($user);

        $this->assertSame(0, $user->tokens()->where('revoked', false)->count());
    }
}
