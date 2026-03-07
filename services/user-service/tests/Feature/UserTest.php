<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['status' => 'active']);
    }

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */

    public function test_can_list_users_for_tenant(): void
    {
        User::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);
        User::factory()->count(2)->create(); // different tenant

        $response = $this->getJsonAsManager('/api/v1/users');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'name', 'email', 'role', 'status']],
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_list_supports_search(): void
    {
        User::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Alice Smith']);
        User::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Bob Jones']);

        $response = $this->getJsonAsManager('/api/v1/users?search=Alice');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Alice Smith', $response->json('data.0.name'));
    }

    public function test_list_supports_role_filter(): void
    {
        User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => 'admin']);
        User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => 'viewer']);
        User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => 'viewer']);

        $response = $this->getJsonAsManager('/api/v1/users?filter[role]=viewer');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    /*
    |--------------------------------------------------------------------------
    | Show
    |--------------------------------------------------------------------------
    */

    public function test_can_show_user(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->getJsonAsManager("/api/v1/users/{$user->id}")
            ->assertOk()
            ->assertJsonPath('id', $user->id);
    }

    public function test_cannot_show_user_from_other_tenant(): void
    {
        $other = User::factory()->create(); // different tenant

        $this->getJsonAsManager("/api/v1/users/{$other->id}")
            ->assertNotFound();
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function test_can_create_user(): void
    {
        $payload = [
            'name'  => 'New User',
            'email' => 'new@example.com',
            'role'  => 'staff',
        ];

        $this->postJsonAsManager('/api/v1/users', $payload)
            ->assertCreated()
            ->assertJsonPath('email', 'new@example.com');
    }

    public function test_create_user_validates_required_fields(): void
    {
        $this->postJsonAsManager('/api/v1/users', [])
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['name', 'email', 'role']]);
    }

    public function test_create_user_rejects_duplicate_email(): void
    {
        $existing = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->postJsonAsManager('/api/v1/users', [
            'name'  => 'Duplicate',
            'email' => $existing->email,
            'role'  => 'viewer',
        ])->assertStatus(409);
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function test_can_update_user(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->putJsonAsManager("/api/v1/users/{$user->id}", ['name' => 'Updated Name'])
            ->assertOk()
            ->assertJsonPath('name', 'Updated Name');
    }

    /*
    |--------------------------------------------------------------------------
    | Delete & Restore
    |--------------------------------------------------------------------------
    */

    public function test_can_delete_user(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->deleteJsonAsManager("/api/v1/users/{$user->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_can_restore_user(): void
    {
        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $user->delete();

        $this->postJsonAsManager("/api/v1/users/{$user->id}/restore")
            ->assertOk();

        $this->assertNotSoftDeleted('users', ['id' => $user->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function getJsonAsManager(string $uri): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders($this->managerHeaders())->getJson($uri);
    }

    private function postJsonAsManager(string $uri, array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders($this->managerHeaders())->postJson($uri, $data);
    }

    private function putJsonAsManager(string $uri, array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders($this->managerHeaders())->putJson($uri, $data);
    }

    private function deleteJsonAsManager(string $uri): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders($this->managerHeaders())->deleteJson($uri);
    }

    /**
     * Returns fake request attributes that simulate a successfully authenticated manager.
     * In real tests these would be injected via middleware mocking.
     */
    private function managerHeaders(): array
    {
        return [
            'X-Tenant-ID' => (string) $this->tenant->id,
            'Accept'      => 'application/json',
        ];
    }
}
