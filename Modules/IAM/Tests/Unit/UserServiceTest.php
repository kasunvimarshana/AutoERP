<?php

declare(strict_types=1);

namespace Modules\IAM\Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IAM\Repositories\Contracts\UserRepositoryInterface;
use Modules\IAM\Services\UserService;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UserService $userService;

    protected UserRepositoryInterface $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = app(UserRepositoryInterface::class);
        $this->userService = app(UserService::class);
    }

    public function test_it_creates_user_successfully(): void
    {
        // Arrange
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'is_active' => true,
        ];

        // Act
        $user = $this->userService->createUser($userData);

        // Assert
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertTrue($user->is_active);
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);
    }

    public function test_it_hashes_password_on_create(): void
    {
        // Arrange
        $userData = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'plainpassword',
        ];

        // Act
        $user = $this->userService->createUser($userData);

        // Assert
        $this->assertNotEquals('plainpassword', $user->password);
        $this->assertTrue(strlen($user->password) > 20); // Hashed password is long
    }

    public function test_it_finds_user_by_id(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $foundUser = $this->userService->getUserById($user->id);

        // Assert
        $this->assertNotNull($foundUser);
        $this->assertEquals($user->id, $foundUser->id);
        $this->assertEquals($user->email, $foundUser->email);
    }

    public function test_it_finds_user_by_email(): void
    {
        // Arrange
        $user = User::factory()->create(['email' => 'test@example.com']);

        // Act
        $foundUser = $this->userService->getUserByEmail('test@example.com');

        // Assert
        $this->assertNotNull($foundUser);
        $this->assertEquals($user->id, $foundUser->id);
    }

    public function test_it_updates_user_successfully(): void
    {
        // Arrange
        $user = User::factory()->create(['name' => 'Old Name']);

        // Act
        $updatedUser = $this->userService->updateUser($user->id, [
            'name' => 'New Name',
        ]);

        // Assert
        $this->assertEquals('New Name', $updatedUser->name);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
        ]);
    }

    public function test_it_deletes_user_successfully(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $result = $this->userService->deleteUser($user->id);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_it_activates_user(): void
    {
        // Arrange
        $user = User::factory()->create(['is_active' => false]);

        // Act
        $activatedUser = $this->userService->activateUser($user->id);

        // Assert
        $this->assertTrue($activatedUser->is_active);
    }

    public function test_it_deactivates_user(): void
    {
        // Arrange
        $user = User::factory()->create(['is_active' => true]);

        // Act
        $deactivatedUser = $this->userService->deactivateUser($user->id);

        // Assert
        $this->assertFalse($deactivatedUser->is_active);
    }

    public function test_it_gets_active_users(): void
    {
        // Arrange
        User::factory()->count(3)->create(['is_active' => true]);
        User::factory()->count(2)->create(['is_active' => false]);

        // Act
        $activeUsers = $this->userService->getActiveUsers();

        // Assert
        $this->assertCount(3, $activeUsers);
        $activeUsers->each(function ($user) {
            $this->assertTrue($user->is_active);
        });
    }

    public function test_it_searches_users(): void
    {
        // Arrange
        User::factory()->create(['name' => 'John Smith', 'email' => 'john@example.com']);
        User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
        User::factory()->create(['name' => 'Bob Johnson', 'email' => 'bob@example.com']);

        // Act
        $results = $this->userService->searchUsers('John');

        // Assert
        $this->assertCount(2, $results); // John Smith and Bob Johnson
    }

    public function test_it_throws_exception_when_user_not_found(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User not found: 999');

        $this->userService->updateUser(999, ['name' => 'Test']);
    }
}
