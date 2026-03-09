<?php

namespace Tests\Feature\Auth;

use App\Domain\Models\Tenant;
use App\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('passport:install', ['--no-interaction' => true]);

        $this->tenant = Tenant::factory()->create([
            'status' => 'active',
            'plan'   => 'pro',
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email'     => 'test@example.com',
            'password'  => Hash::make('ValidPassword@123'),
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_logs_in_with_valid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'     => 'test@example.com',
            'password'  => 'ValidPassword@123',
            'tenant_id' => $this->tenant->id,
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'access_token',
                         'token_type',
                         'expires_in',
                         'user' => ['id', 'email', 'name'],
                     ],
                 ])
                 ->assertJson(['success' => true])
                 ->assertJsonPath('data.token_type', 'Bearer');
    }

    /** @test */
    public function it_fails_login_with_wrong_password(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'     => 'test@example.com',
            'password'  => 'WrongPassword!',
            'tenant_id' => $this->tenant->id,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_fails_login_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'     => 'nobody@example.com',
            'password'  => 'AnyPassword@123',
            'tenant_id' => $this->tenant->id,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_fails_login_with_invalid_tenant(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'     => 'test@example.com',
            'password'  => 'ValidPassword@123',
            'tenant_id' => '00000000-0000-0000-0000-000000000000',
        ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function it_fails_login_for_inactive_user(): void
    {
        $this->user->update(['is_active' => false]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'     => 'test@example.com',
            'password'  => 'ValidPassword@123',
            'tenant_id' => $this->tenant->id,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_fails_login_for_suspended_tenant(): void
    {
        $this->tenant->update(['status' => 'suspended']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'     => 'test@example.com',
            'password'  => 'ValidPassword@123',
            'tenant_id' => $this->tenant->id,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['tenant']);
    }

    /** @test */
    public function it_enforces_multi_tenant_isolation(): void
    {
        // Create a second tenant with a user that has the same email
        $tenant2 = Tenant::factory()->create(['status' => 'active']);
        User::factory()->create([
            'tenant_id' => $tenant2->id,
            'email'     => 'test@example.com',
            'password'  => Hash::make('DifferentPassword@456'),
            'is_active' => true,
        ]);

        // Login to tenant1 with tenant1's password
        $response = $this->postJson('/api/v1/auth/login', [
            'email'     => 'test@example.com',
            'password'  => 'ValidPassword@123',
            'tenant_id' => $this->tenant->id,
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('data.user.tenant_id', $this->tenant->id);
    }

    /** @test */
    public function it_returns_validation_error_for_missing_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'password'  => 'ValidPassword@123',
            'tenant_id' => $this->tenant->id,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_returns_validation_error_for_missing_password(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'     => 'test@example.com',
            'tenant_id' => $this->tenant->id,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function it_records_last_login_timestamp(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email'     => 'test@example.com',
            'password'  => 'ValidPassword@123',
            'tenant_id' => $this->tenant->id,
        ]);

        $this->user->refresh();
        $this->assertNotNull($this->user->last_login_at);
    }

    /** @test */
    public function it_returns_roles_and_permissions_on_login(): void
    {
        $this->user->assignRole('viewer');

        $response = $this->postJson('/api/v1/auth/login', [
            'email'     => 'test@example.com',
            'password'  => 'ValidPassword@123',
            'tenant_id' => $this->tenant->id,
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'roles',
                         'permissions',
                     ],
                 ]);
    }
}
