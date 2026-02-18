<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IAM\Entities\Role;
use Modules\IAM\Repositories\RoleRepository;
use Tests\TestCase;

class RoleRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected RoleRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new RoleRepository(new Role);
    }

    public function test_it_creates_role_successfully(): void
    {
        // Arrange
        $data = [
            'name' => 'Admin',
            'slug' => 'admin',
            'description' => 'Administrator role',
            'is_active' => true,
            'is_system' => false,
        ];

        // Act
        $role = $this->repository->create($data);

        // Assert
        $this->assertInstanceOf(Role::class, $role);
        $this->assertEquals('Admin', $role->name);
        $this->assertEquals('admin', $role->slug);
        $this->assertDatabaseHas('roles', [
            'name' => 'Admin',
            'slug' => 'admin',
        ]);
    }

    public function test_it_finds_role_by_id(): void
    {
        // Arrange
        $role = Role::factory()->create();

        // Act
        $foundRole = $this->repository->findById($role->id);

        // Assert
        $this->assertNotNull($foundRole);
        $this->assertEquals($role->id, $foundRole->id);
    }

    public function test_it_finds_role_by_slug(): void
    {
        // Arrange
        $role = Role::factory()->create(['slug' => 'test-role']);

        // Act
        $foundRole = $this->repository->findBySlug('test-role');

        // Assert
        $this->assertNotNull($foundRole);
        $this->assertEquals($role->id, $foundRole->id);
    }

    public function test_it_gets_all_roles(): void
    {
        // Arrange
        Role::factory()->count(3)->create();

        // Act
        $roles = $this->repository->all();

        // Assert
        $this->assertCount(3, $roles);
    }

    public function test_it_gets_paginated_roles(): void
    {
        // Arrange
        Role::factory()->count(20)->create();

        // Act
        $paginatedRoles = $this->repository->paginate(10);

        // Assert
        $this->assertEquals(10, $paginatedRoles->perPage());
        $this->assertEquals(20, $paginatedRoles->total());
        $this->assertCount(10, $paginatedRoles->items());
    }

    public function test_it_updates_role_successfully(): void
    {
        // Arrange
        $role = Role::factory()->create(['name' => 'Old Name']);

        // Act
        $updatedRole = $this->repository->update($role, ['name' => 'New Name']);

        // Assert
        $this->assertEquals('New Name', $updatedRole->name);
        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'New Name',
        ]);
    }

    public function test_it_deletes_role_successfully(): void
    {
        // Arrange
        $role = Role::factory()->create(['is_system' => false]);

        // Act
        $result = $this->repository->delete($role);

        // Assert
        $this->assertTrue($result);
        $this->assertSoftDeleted('roles', [
            'id' => $role->id,
        ]);
    }

    public function test_it_gets_active_roles(): void
    {
        // Arrange
        Role::factory()->count(3)->create(['is_active' => true]);
        Role::factory()->count(2)->create(['is_active' => false]);

        // Act
        $activeRoles = $this->repository->findActive();

        // Assert
        $this->assertCount(3, $activeRoles);
        $activeRoles->each(function ($role) {
            $this->assertTrue($role->is_active);
        });
    }

    public function test_it_gets_system_roles(): void
    {
        // Arrange
        Role::factory()->count(2)->create(['is_system' => true]);
        Role::factory()->count(3)->create(['is_system' => false]);

        // Act
        $systemRoles = $this->repository->findSystem();

        // Assert
        $this->assertCount(2, $systemRoles);
        $systemRoles->each(function ($role) {
            $this->assertTrue($role->is_system);
        });
    }

    public function test_it_gets_custom_roles(): void
    {
        // Arrange
        Role::factory()->count(2)->create(['is_system' => true]);
        Role::factory()->count(3)->create(['is_system' => false]);

        // Act
        $customRoles = $this->repository->findCustom();

        // Assert
        $this->assertCount(3, $customRoles);
        $customRoles->each(function ($role) {
            $this->assertFalse($role->is_system);
        });
    }

    public function test_it_gets_top_level_roles(): void
    {
        // Arrange
        $parent = Role::factory()->create();
        Role::factory()->count(2)->create(['parent_id' => null]);
        Role::factory()->count(3)->create(['parent_id' => $parent->id]);

        // Act
        $topLevelRoles = $this->repository->findTopLevel();

        // Assert
        $this->assertCount(3, $topLevelRoles); // 2 created + 1 parent
    }

    public function test_it_searches_roles(): void
    {
        // Arrange
        Role::factory()->create(['name' => 'Admin Role', 'slug' => 'admin-role']);
        Role::factory()->create(['name' => 'User Role', 'slug' => 'user-role']);
        Role::factory()->create(['name' => 'Guest Role', 'slug' => 'guest-role']);

        // Act
        $results = $this->repository->search('Admin');

        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals('Admin Role', $results->first()->name);
    }
}
