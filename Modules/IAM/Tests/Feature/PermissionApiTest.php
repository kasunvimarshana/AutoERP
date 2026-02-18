<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IAM\Entities\Permission;
use Tests\TestCase;

class PermissionApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'permissions' => [
                'iam.permission.view',
                'iam.permission.create',
                'iam.permission.update',
                'iam.permission.delete',
            ],
        ]);
    }

    public function test_it_lists_permissions(): void
    {
        Permission::factory()->count(3)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/iam/permissions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'module',
                        'resource',
                        'action',
                    ],
                ],
            ]);
    }

    public function test_it_creates_permission(): void
    {
        $data = [
            'name' => 'Test Permission',
            'slug' => 'test.permission',
            'module' => 'test',
            'resource' => 'resource',
            'action' => 'view',
            'description' => 'Test description',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/iam/permissions', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Test Permission')
            ->assertJsonPath('data.slug', 'test.permission');

        $this->assertDatabaseHas('permissions', [
            'slug' => 'test.permission',
        ]);
    }

    public function test_it_shows_permission(): void
    {
        $permission = Permission::factory()->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/iam/permissions/{$permission->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $permission->id)
            ->assertJsonPath('data.name', $permission->name);
    }

    public function test_it_updates_permission(): void
    {
        $permission = Permission::factory()->create();

        $data = [
            'name' => 'Updated Permission Name',
            'description' => 'Updated description',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/iam/permissions/{$permission->id}", $data);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Permission Name');

        $this->assertDatabaseHas('permissions', [
            'id' => $permission->id,
            'name' => 'Updated Permission Name',
        ]);
    }

    public function test_it_deletes_permission(): void
    {
        $permission = Permission::factory()->create();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/iam/permissions/{$permission->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('permissions', [
            'id' => $permission->id,
        ]);
    }

    public function test_it_filters_active_permissions(): void
    {
        Permission::factory()->create(['is_active' => true]);
        Permission::factory()->create(['is_active' => false]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/iam/permissions/active');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    public function test_it_searches_permissions(): void
    {
        Permission::factory()->create(['name' => 'Customer View']);
        Permission::factory()->create(['name' => 'Order Create']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/iam/permissions/search?q=Customer');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Customer View', $data[0]['name']);
    }

    public function test_unauthorized_user_cannot_create_permission(): void
    {
        $unauthorizedUser = User::factory()->create([
            'permissions' => [],
        ]);

        $response = $this->actingAs($unauthorizedUser)
            ->postJson('/api/v1/iam/permissions', [
                'name' => 'Test',
                'slug' => 'test',
            ]);

        $response->assertStatus(403);
    }
}
