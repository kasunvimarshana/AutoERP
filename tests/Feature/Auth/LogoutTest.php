<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class LogoutTest extends TestCase
{
    use RefreshDatabase;

    protected bool $setUpPassportClient = true;

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password123'),
            'status'   => 'active',
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'Password123',
        ]);

        $loginResponse->assertStatus(200);
        $token = $loginResponse->json('data.access_token');

        $logoutResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout');

        $logoutResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        // Verify the token was actually revoked in the database
        $user->refresh();
        $this->assertDatabaseHas('oauth_access_tokens', [
            'user_id' => $user->id,
            'revoked'  => true,
        ]);
    }

    public function test_user_can_logout_all_devices(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password123'),
            'status'   => 'active',
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'Password123',
        ]);

        $loginResponse->assertStatus(200);
        $token = $loginResponse->json('data.access_token');

        $logoutAllResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout-all');

        $logoutAllResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        // Verify all tokens revoked
        $this->assertDatabaseMissing('oauth_access_tokens', [
            'user_id' => $user->id,
            'revoked'  => false,
        ]);
    }

    public function test_logout_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');
        $response->assertStatus(401);
    }
}
