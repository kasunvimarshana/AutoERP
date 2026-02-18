<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Modules\IAM\Entities\Permission;
use Modules\IAM\Repositories\Contracts\PermissionRepositoryInterface;
use Modules\IAM\Services\PermissionService;
use Tests\TestCase;

class PermissionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_creates_permission_with_auto_generated_slug(): void
    {
        $mockRepo = Mockery::mock(PermissionRepositoryInterface::class);
        $service = new PermissionService($mockRepo);

        $data = [
            'name' => 'Test Permission',
            'module' => 'test',
        ];

        $expectedPermission = new Permission([
            'name' => 'Test Permission',
            'slug' => 'test-permission',
            'module' => 'test',
        ]);

        $mockRepo->shouldReceive('findBySlug')
            ->with('test-permission')
            ->once()
            ->andReturn(null);

        $mockRepo->shouldReceive('create')
            ->once()
            ->andReturn($expectedPermission);

        $result = $service->createPermission($data);

        $this->assertEquals('Test Permission', $result->name);
    }

    public function test_it_throws_exception_for_duplicate_slug(): void
    {
        $mockRepo = Mockery::mock(PermissionRepositoryInterface::class);
        $service = new PermissionService($mockRepo);

        $existingPermission = new Permission(['slug' => 'existing-permission']);

        $mockRepo->shouldReceive('findBySlug')
            ->with('existing-permission')
            ->once()
            ->andReturn($existingPermission);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Permission with slug 'existing-permission' already exists");

        $service->createPermission([
            'name' => 'Test',
            'slug' => 'existing-permission',
        ]);
    }

    public function test_it_generates_crud_permissions(): void
    {
        $mockRepo = Mockery::mock(PermissionRepositoryInterface::class);
        $service = new PermissionService($mockRepo);

        $mockRepo->shouldReceive('findBySlug')
            ->times(4)
            ->andReturn(null);

        $mockRepo->shouldReceive('create')
            ->times(4)
            ->andReturn(new Permission);

        $permissions = $service->generateCrudPermissions('sales', 'customer');

        $this->assertCount(4, $permissions);
    }
}
