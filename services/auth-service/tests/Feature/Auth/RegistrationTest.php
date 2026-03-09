<?php

namespace Tests\Feature\Auth;

use App\Domain\Models\Tenant;
use App\Domain\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('passport:install', ['--no-interaction' => true]);

        $this->tenant = Tenant::factory()->create([
            'status' => 'active',
            'plan'   => 'pro',
        ]);
    }

    /** @test */
    public function it_registers_a_new_user_successfully(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'tenant_id'             => $this->tenant->id,
            'name'                  => 'Jane Doe',
            'email'                 => 'jane@example.com',
            'password'              => 'StrongPass@2024!',
            'password_confirmation' => 'StrongPass@2024!',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'access_token',
                         'token_type',
                         'expires_in',
                         'user' => ['id', 'email', 'name', 'tenant_id'],
                     ],
                 ])
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', [
            'email'     => 'jane@example.com',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    /** @test */
    public function it_rejects_duplicate_email_within_same_tenant(): void
    {
        User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email'     => 'existing@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'tenant_id'             => $this->tenant->id,
            'name'                  => 'Duplicate User',
            'email'                 => 'existing@example.com',
            'password'              => 'StrongPass@2024!',
            'password_confirmation' => 'StrongPass@2024!',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function it_requires_strong_password(): void
    {
        $weakPasswords = ['password', '12345678', 'password1', 'PASSWORD1'];

        foreach ($weakPasswords as $weak) {
            $response = $this->postJson('/api/v1/auth/register', [
                'tenant_id'             => $this->tenant->id,
                'name'                  => 'Test User',
                'email'                 => 'test' . uniqid() . '@example.com',
                'password'              => $weak,
                'password_confirmation' => $weak,
            ]);

            $response->assertStatus(422)
                     ->assertJsonValidationErrors(['password']);
        }
    }

    /** @test */
    public function it_requires_password_confirmation(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'tenant_id'             => $this->tenant->id,
            'name'                  => 'Test User',
            'email'                 => 'test@example.com',
            'password'              => 'StrongPass@2024!',
            'password_confirmation' => 'DifferentPass@2024!',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function it_fails_for_nonexistent_tenant(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'tenant_id'             => '00000000-0000-0000-0000-000000000000',
            'name'                  => 'Test User',
            'email'                 => 'test@example.com',
            'password'              => 'StrongPass@2024!',
            'password_confirmation' => 'StrongPass@2024!',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['tenant_id']);
    }

    /** @test */
    public function it_assigns_default_viewer_role(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'tenant_id'             => $this->tenant->id,
            'name'                  => 'New User',
            'email'                 => 'newuser@example.com',
            'password'              => 'StrongPass@2024!',
            'password_confirmation' => 'StrongPass@2024!',
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'newuser@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('viewer'));
    }

    /** @test */
    public function it_fails_when_tenant_plan_user_limit_exceeded(): void
    {
        // Tenant on free plan with 5 user limit
        $freeTenant = Tenant::factory()->create([
            'status' => 'active',
            'plan'   => 'free',
        ]);

        // Create 5 users (at the limit)
        User::factory()->count(5)->create(['tenant_id' => $freeTenant->id]);

        $response = $this->postJson('/api/v1/auth/register', [
            'tenant_id'             => $freeTenant->id,
            'name'                  => 'Overflow User',
            'email'                 => 'overflow@example.com',
            'password'              => 'StrongPass@2024!',
            'password_confirmation' => 'StrongPass@2024!',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['tenant_id']);
    }

    /** @test */
    public function it_requires_all_mandatory_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['tenant_id', 'name', 'email', 'password']);
    }
}
