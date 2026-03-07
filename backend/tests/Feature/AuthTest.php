<?php
namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('passport:keys', ['--force' => true]);
        $this->artisan('passport:client', ['--personal' => true, '--name' => 'Test', '--provider' => 'users']);

        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'domain' => 'test',
            'is_active' => true,
        ]);
    }

    public function test_user_can_login(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@test.com',
            'password' => bcrypt('password'),
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@test.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['success', 'access_token', 'token_type', 'user']);
    }

    public function test_invalid_credentials_return_401(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'wrong@test.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }
}
