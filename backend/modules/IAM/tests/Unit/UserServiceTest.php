<?php

namespace Modules\IAM\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Modules\Core\Services\TenantContext;
use Modules\IAM\DTOs\UserDTO;
use Modules\IAM\Models\User;
use Modules\IAM\Repositories\UserRepository;
use Modules\IAM\Services\UserService;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UserService $userService;

    protected TenantContext $tenantContext;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantContext = Mockery::mock(TenantContext::class);
        $this->tenantContext->shouldReceive('getTenantId')->andReturn(1);
        $this->tenantContext->shouldReceive('hasTenant')->andReturn(true);

        $userRepository = new UserRepository($this->tenantContext);
        $this->userService = new UserService($userRepository, $this->tenantContext);

        $this->artisan('migrate:fresh');
    }

    public function test_can_create_user(): void
    {
        $dto = new UserDTO([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'is_active' => true,
        ]);

        $user = $this->userService->create($dto);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertEquals(1, $user->tenant_id);
    }

    public function test_cannot_create_user_with_duplicate_email(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        User::factory()->create([
            'email' => 'test@example.com',
            'tenant_id' => 1,
        ]);

        $dto = new UserDTO([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'is_active' => true,
        ]);

        $this->userService->create($dto);
    }

    public function test_can_update_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'tenant_id' => 1,
        ]);

        $dto = new UserDTO([
            'name' => 'New Name',
            'email' => 'old@example.com',
            'is_active' => true,
        ]);

        $updatedUser = $this->userService->update($user->id, $dto);

        $this->assertEquals('New Name', $updatedUser->name);
    }

    public function test_can_delete_user(): void
    {
        $user = User::factory()->create(['tenant_id' => 1]);

        $this->userService->delete($user->id);

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_can_activate_user(): void
    {
        $user = User::factory()->create([
            'is_active' => false,
            'tenant_id' => 1,
        ]);

        $activated = $this->userService->activate($user->id);

        $this->assertTrue($activated->is_active);
    }

    public function test_can_deactivate_user(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'tenant_id' => 1,
        ]);

        $deactivated = $this->userService->deactivate($user->id);

        $this->assertFalse($deactivated->is_active);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
