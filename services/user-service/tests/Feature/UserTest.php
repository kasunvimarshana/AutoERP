<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\UserCreated;
use App\Events\UserDeleted;
use App\Events\UserUpdated;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    private UserService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(UserService::class);

        // Prevent listeners from attempting a real RabbitMQ connection during tests
        Event::fake([UserCreated::class, UserUpdated::class, UserDeleted::class]);
    }

    // -------------------------------------------------------------------------
    // INDEX
    // -------------------------------------------------------------------------

    public function test_index_returns_paginated_users(): void
    {
        User::factory()->count(20)->create();

        $result = $this->service->getAllUsers(['per_page' => 10]);

        $this->assertSame(10, $result->perPage());
        $this->assertSame(20, $result->total());
    }

    public function test_index_filters_by_department(): void
    {
        User::factory()->count(3)->create(['department' => 'Engineering']);
        User::factory()->count(2)->create(['department' => 'Finance']);

        $result = $this->service->getAllUsers(['department' => 'Engineering']);

        $this->assertSame(3, $result->total());
        $result->getCollection()->each(
            fn (User $u) => $this->assertSame('Engineering', $u->department)
        );
    }

    public function test_index_filters_by_active_status(): void
    {
        User::factory()->count(4)->active()->create();
        User::factory()->count(1)->inactive()->create();

        $result = $this->service->getAllUsers(['is_active' => 'true']);

        $this->assertSame(4, $result->total());
    }

    public function test_index_searches_by_name(): void
    {
        User::factory()->create(['first_name' => 'Unique', 'last_name' => 'Person']);
        User::factory()->create(['first_name' => 'Common', 'last_name' => 'User']);

        $result = $this->service->getAllUsers(['search' => 'Unique']);

        $this->assertSame(1, $result->total());
        $this->assertSame('Unique', $result->getCollection()->first()->first_name);
    }

    public function test_index_filters_by_role(): void
    {
        User::factory()->withRoles('admin')->count(2)->create();
        User::factory()->withRoles('viewer')->count(3)->create();

        $result = $this->service->getAllUsers(['role' => 'admin']);

        $this->assertSame(2, $result->total());
    }

    public function test_index_sorts_by_email_ascending(): void
    {
        User::factory()->create(['email' => 'charlie@example.com']);
        User::factory()->create(['email' => 'alice@example.com']);
        User::factory()->create(['email' => 'bob@example.com']);

        $result = $this->service->getAllUsers([
            'sort_by'        => 'email',
            'sort_direction' => 'asc',
        ]);

        $emails = $result->getCollection()->pluck('email')->toArray();
        $this->assertSame(['alice@example.com', 'bob@example.com', 'charlie@example.com'], $emails);
    }

    // -------------------------------------------------------------------------
    // SHOW
    // -------------------------------------------------------------------------

    public function test_get_user_by_id_returns_user(): void
    {
        $user = User::factory()->create();

        $found = $this->service->getUserById($user->id);

        $this->assertNotNull($found);
        $this->assertSame($user->id, $found->id);
    }

    public function test_get_user_by_id_returns_null_for_missing_id(): void
    {
        $result = $this->service->getUserById(99999);

        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // CREATE
    // -------------------------------------------------------------------------

    public function test_create_user_persists_and_fires_event(): void
    {
        $data = [
            'email'      => 'newuser@example.com',
            'first_name' => 'New',
            'last_name'  => 'User',
            'username'   => 'newuser',
            'roles'      => ['viewer'],
            'is_active'  => true,
        ];

        $user = $this->service->createUser($data);

        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
        $this->assertSame('New', $user->first_name);

        Event::assertDispatched(UserCreated::class, function (UserCreated $e) use ($user): bool {
            return $e->user->id === $user->id;
        });
    }

    public function test_create_user_defaults_roles_to_empty_array(): void
    {
        $user = $this->service->createUser([
            'email'      => 'noroles@example.com',
            'first_name' => 'No',
            'last_name'  => 'Roles',
            'username'   => 'noroles',
        ]);

        $this->assertSame([], $user->roles);
    }

    // -------------------------------------------------------------------------
    // UPDATE
    // -------------------------------------------------------------------------

    public function test_update_user_persists_changes_and_fires_event(): void
    {
        $user = User::factory()->create(['first_name' => 'Original']);

        $updated = $this->service->updateUser($user->id, ['first_name' => 'Updated']);

        $this->assertNotNull($updated);
        $this->assertSame('Updated', $updated->first_name);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'first_name' => 'Updated']);

        Event::assertDispatched(UserUpdated::class, function (UserUpdated $e) use ($user): bool {
            return $e->user->id === $user->id;
        });
    }

    public function test_update_user_returns_null_for_missing_id(): void
    {
        $result = $this->service->updateUser(99999, ['first_name' => 'Ghost']);

        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // DELETE
    // -------------------------------------------------------------------------

    public function test_delete_user_soft_deletes_and_fires_event(): void
    {
        $user = User::factory()->create();

        $result = $this->service->deleteUser($user->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('users', ['id' => $user->id]);

        Event::assertDispatched(UserDeleted::class, function (UserDeleted $e) use ($user): bool {
            return $e->userId === $user->id;
        });
    }

    public function test_delete_user_returns_false_for_missing_id(): void
    {
        $result = $this->service->deleteUser(99999);

        $this->assertFalse($result);
        Event::assertNotDispatched(UserDeleted::class);
    }

    // -------------------------------------------------------------------------
    // ROLE MANAGEMENT
    // -------------------------------------------------------------------------

    public function test_assign_role_adds_role_to_user(): void
    {
        $user = User::factory()->withRoles('viewer')->create();

        $updated = $this->service->assignRole($user->id, 'manager');

        $this->assertNotNull($updated);
        $this->assertContains('manager', $updated->roles);
        $this->assertContains('viewer', $updated->roles);
    }

    public function test_assign_role_is_idempotent(): void
    {
        $user = User::factory()->withRoles('admin')->create();

        $updated = $this->service->assignRole($user->id, 'admin');

        $this->assertCount(1, $updated->roles);
    }

    public function test_revoke_role_removes_role_from_user(): void
    {
        $user = User::factory()->withRoles('viewer', 'manager')->create();

        $updated = $this->service->revokeRole($user->id, 'manager');

        $this->assertNotNull($updated);
        $this->assertNotContains('manager', $updated->roles);
        $this->assertContains('viewer', $updated->roles);
    }

    public function test_assign_role_returns_null_for_missing_user(): void
    {
        $result = $this->service->assignRole(99999, 'admin');

        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // SYNC FROM KEYCLOAK
    // -------------------------------------------------------------------------

    public function test_sync_from_keycloak_creates_new_user(): void
    {
        $keycloakId = 'kc-uuid-test-001';

        $user = $this->service->syncFromKeycloak($keycloakId, [
            'email'      => 'synced@example.com',
            'first_name' => 'Synced',
            'last_name'  => 'User',
            'username'   => 'synced.user',
            'roles'      => ['viewer'],
            'is_active'  => true,
        ]);

        $this->assertNotNull($user);
        $this->assertDatabaseHas('users', ['keycloak_id' => $keycloakId, 'email' => 'synced@example.com']);
    }

    public function test_sync_from_keycloak_updates_existing_user(): void
    {
        $keycloakId = 'kc-uuid-existing-001';
        $user       = User::factory()->create(['keycloak_id' => $keycloakId, 'first_name' => 'Old']);

        $updated = $this->service->syncFromKeycloak($keycloakId, ['first_name' => 'New']);

        $this->assertSame($user->id, $updated->id);
        $this->assertSame('New', $updated->first_name);
    }

    // -------------------------------------------------------------------------
    // HTTP Layer
    // -------------------------------------------------------------------------

    public function test_http_index_returns_users_list(): void
    {
        User::factory()->count(3)->create();

        $response = $this->withoutMiddleware()->getJson('/api/v1/users');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [['id', 'email', 'first_name', 'last_name', 'roles', 'is_active']],
        ]);
    }

    public function test_http_store_validates_required_fields(): void
    {
        $response = $this->withoutMiddleware()->postJson('/api/v1/users', []);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);
    }

    public function test_http_store_creates_user(): void
    {
        $payload = [
            'email'      => 'http.user@example.com',
            'first_name' => 'HTTP',
            'last_name'  => 'User',
            'username'   => 'http.user',
        ];

        $response = $this->withoutMiddleware()->postJson('/api/v1/users', $payload);

        $response->assertStatus(201);
        $response->assertJsonFragment(['email' => 'http.user@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'http.user@example.com']);
    }

    public function test_http_show_returns_404_for_unknown_user(): void
    {
        $response = $this->withoutMiddleware()->getJson('/api/v1/users/99999');

        $response->assertNotFound();
    }

    public function test_http_update_user(): void
    {
        $user = User::factory()->create();

        $response = $this->withoutMiddleware()->putJson(
            "/api/v1/users/{$user->id}",
            ['first_name' => 'Updated']
        );

        $response->assertOk();
        $response->assertJsonFragment(['first_name' => 'Updated']);
    }

    public function test_http_destroy_user(): void
    {
        $user = User::factory()->create();

        $response = $this->withoutMiddleware()->deleteJson("/api/v1/users/{$user->id}");

        $response->assertOk();
        $response->assertJsonFragment(['message' => 'User deleted successfully.']);
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_http_assign_role(): void
    {
        $user = User::factory()->withRoles('viewer')->create();

        $response = $this->withoutMiddleware()->postJson(
            "/api/v1/users/{$user->id}/assign-role",
            ['role' => 'manager']
        );

        $response->assertOk();
        $response->assertJsonPath('data.roles', fn (array $roles) => in_array('manager', $roles, true));
    }

    public function test_http_revoke_role(): void
    {
        $user = User::factory()->withRoles('viewer', 'manager')->create();

        $response = $this->withoutMiddleware()->postJson(
            "/api/v1/users/{$user->id}/revoke-role",
            ['role' => 'manager']
        );

        $response->assertOk();
        $response->assertJsonPath('data.roles', fn (array $roles) => ! in_array('manager', $roles, true));
    }

    public function test_http_health_check(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertOk();
        $response->assertJsonFragment(['service' => 'user-service', 'status' => 'ok']);
    }
}
