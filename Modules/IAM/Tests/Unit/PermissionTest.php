<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IAM\Entities\Permission;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_permission_successfully(): void
    {
        $permission = Permission::create([
            'name' => 'Test Permission',
            'slug' => 'test.permission',
            'module' => 'test',
            'resource' => 'permission',
            'action' => 'view',
            'description' => 'Test permission description',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('permissions', [
            'id' => $permission->id,
            'name' => 'Test Permission',
            'slug' => 'test.permission',
        ]);
    }

    public function test_it_returns_full_identifier(): void
    {
        $permission = Permission::create([
            'name' => 'Test Permission',
            'slug' => 'test.permission',
            'module' => 'sales',
            'resource' => 'customer',
            'action' => 'view',
        ]);

        $this->assertEquals('sales.customer.view', $permission->full_identifier);
    }

    public function test_it_scopes_active_permissions(): void
    {
        Permission::create([
            'name' => 'Active Permission',
            'slug' => 'active.permission',
            'is_active' => true,
        ]);

        Permission::create([
            'name' => 'Inactive Permission',
            'slug' => 'inactive.permission',
            'is_active' => false,
        ]);

        $activePermissions = Permission::active()->get();

        $this->assertCount(1, $activePermissions);
        $this->assertEquals('Active Permission', $activePermissions->first()->name);
    }

    public function test_it_scopes_by_module(): void
    {
        Permission::create([
            'name' => 'Sales Permission',
            'slug' => 'sales.permission',
            'module' => 'sales',
        ]);

        Permission::create([
            'name' => 'IAM Permission',
            'slug' => 'iam.permission',
            'module' => 'iam',
        ]);

        $salesPermissions = Permission::forModule('sales')->get();

        $this->assertCount(1, $salesPermissions);
        $this->assertEquals('Sales Permission', $salesPermissions->first()->name);
    }

    public function test_it_casts_metadata_to_array(): void
    {
        $permission = Permission::create([
            'name' => 'Test Permission',
            'slug' => 'test.permission',
            'metadata' => ['key' => 'value', 'number' => 123],
        ]);

        $this->assertIsArray($permission->metadata);
        $this->assertEquals('value', $permission->metadata['key']);
        $this->assertEquals(123, $permission->metadata['number']);
    }
}
