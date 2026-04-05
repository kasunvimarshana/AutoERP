<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\Exceptions\UserNotFoundException;
use Modules\User\Domain\Exceptions\InvalidCredentialsException;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;
use Modules\User\Application\DTOs\UpdateProfileData;
use Modules\User\Application\Services\GetUserService;
use Modules\User\Application\Services\DeleteUserService;

class UserProfileModuleTest extends TestCase
{
    // ──────────────────────────────────────────────────────────────────────
    // Helper factories
    // ──────────────────────────────────────────────────────────────────────

    private function makeUser(int $id = 1, string $status = 'active'): User
    {
        return new User(
            $id, 1, 'Alice Smith', 'alice@example.com', 'hashed_pw',
            $status, '+1234567890', null, null, null,
            new \DateTime(), new \DateTime(),
        );
    }

    // ──────────────────────────────────────────────────────────────────────
    // User entity – profile management methods
    // ──────────────────────────────────────────────────────────────────────

    public function test_user_activate(): void
    {
        $user = $this->makeUser(1, 'inactive');
        $this->assertFalse($user->isActive());
        $user->activate();
        $this->assertTrue($user->isActive());
        $this->assertEquals('active', $user->getStatus());
    }

    public function test_user_deactivate_from_active(): void
    {
        $user = $this->makeUser();
        $this->assertTrue($user->isActive());
        $user->deactivate();
        $this->assertFalse($user->isActive());
        $this->assertEquals('inactive', $user->getStatus());
    }

    public function test_user_update_profile_changes_name_and_phone(): void
    {
        $user = $this->makeUser();
        $user->updateProfile('Bob Jones', '+9876543210');
        $this->assertEquals('Bob Jones', $user->getName());
        $this->assertEquals('+9876543210', $user->getPhone());
    }

    public function test_user_update_profile_clears_phone_when_null(): void
    {
        $user = $this->makeUser();
        $user->updateProfile('Bob Jones', null);
        $this->assertNull($user->getPhone());
    }

    public function test_user_change_password_updates_hash(): void
    {
        $user = $this->makeUser();
        $user->changePassword('new_hashed_password');
        $this->assertEquals('new_hashed_password', $user->getPassword());
    }

    public function test_user_update_avatar_sets_path(): void
    {
        $user = $this->makeUser();
        $this->assertNull($user->getAvatar());
        $user->updateAvatar('avatars/alice.jpg');
        $this->assertEquals('avatars/alice.jpg', $user->getAvatar());
    }

    public function test_user_update_avatar_clears_when_null(): void
    {
        $user = new User(
            1, 1, 'Alice', 'alice@example.com', 'pw', 'active',
            null, 'avatars/old.jpg', null, null,
            new \DateTime(), new \DateTime(),
        );
        $user->updateAvatar(null);
        $this->assertNull($user->getAvatar());
    }

    public function test_user_update_preferences_stores_array(): void
    {
        $user = $this->makeUser();
        $this->assertNull($user->getPreferences());

        $prefs = ['theme' => 'dark', 'lang' => 'en', 'notifications' => true];
        $user->updatePreferences($prefs);
        $this->assertEquals($prefs, $user->getPreferences());
    }

    public function test_user_update_preferences_can_be_cleared(): void
    {
        $user = new User(
            1, 1, 'Alice', 'alice@example.com', 'pw', 'active',
            null, null, ['theme' => 'light'], null,
            new \DateTime(), new \DateTime(),
        );
        $user->updatePreferences(null);
        $this->assertNull($user->getPreferences());
    }

    public function test_user_getters_return_correct_values(): void
    {
        $created = new \DateTime('2024-01-01');
        $updated = new \DateTime('2024-06-01');
        $verified = new \DateTime('2024-02-01');

        $user = new User(
            42, 5, 'Carol White', 'carol@example.com', 'hashed',
            'active', '+111222333', 'avatars/carol.png',
            ['lang' => 'fr'], $verified, $created, $updated,
        );

        $this->assertEquals(42, $user->getId());
        $this->assertEquals(5, $user->getTenantId());
        $this->assertEquals('Carol White', $user->getName());
        $this->assertEquals('carol@example.com', $user->getEmail());
        $this->assertEquals('hashed', $user->getPassword());
        $this->assertEquals('active', $user->getStatus());
        $this->assertEquals('+111222333', $user->getPhone());
        $this->assertEquals('avatars/carol.png', $user->getAvatar());
        $this->assertEquals(['lang' => 'fr'], $user->getPreferences());
        $this->assertEquals($verified, $user->getEmailVerifiedAt());
        $this->assertEquals($created, $user->getCreatedAt());
        $this->assertEquals($updated, $user->getUpdatedAt());
        $this->assertTrue($user->isActive());
    }

    public function test_user_with_null_id_is_new(): void
    {
        $user = new User(
            null, 1, 'New User', 'new@example.com', 'pw',
            'active', null, null, null, null,
            new \DateTime(), new \DateTime(),
        );
        $this->assertNull($user->getId());
    }

    // ──────────────────────────────────────────────────────────────────────
    // GetUserService – these services do not dispatch events and are safe
    // to test as pure units.
    // ──────────────────────────────────────────────────────────────────────

    public function test_get_user_service_returns_found_user(): void
    {
        /** @var UserRepositoryInterface&MockObject $repo */
        $repo = $this->createMock(UserRepositoryInterface::class);
        $repo->expects($this->once())
            ->method('findById')
            ->with(42)
            ->willReturn($this->makeUser(42));

        $service = new GetUserService($repo);
        $result  = $service->findById(42);

        $this->assertEquals(42, $result->getId());
        $this->assertEquals('Alice Smith', $result->getName());
    }

    public function test_get_user_service_throws_not_found_for_unknown_id(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $service = new GetUserService($repo);
        $this->expectException(UserNotFoundException::class);
        $service->findById(999);
    }

    // ──────────────────────────────────────────────────────────────────────
    // DeleteUserService
    // ──────────────────────────────────────────────────────────────────────

    public function test_delete_user_service_deletes_existing_user(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $repo->method('findById')->willReturn($this->makeUser(1));
        $repo->expects($this->once())->method('delete')->with(1)->willReturn(true);

        $service = new DeleteUserService($repo);
        $result  = $service->execute(1);

        $this->assertTrue($result);
    }

    public function test_delete_user_service_throws_not_found(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $service = new DeleteUserService($repo);
        $this->expectException(UserNotFoundException::class);
        $service->execute(999);
    }

    // ──────────────────────────────────────────────────────────────────────
    // InvalidCredentialsException
    // ──────────────────────────────────────────────────────────────────────

    public function test_invalid_credentials_exception_message(): void
    {
        $e = new InvalidCredentialsException();
        $this->assertStringContainsString('Invalid credentials', $e->getMessage());
    }

    // ──────────────────────────────────────────────────────────────────────
    // UserNotFoundException
    // ──────────────────────────────────────────────────────────────────────

    public function test_user_not_found_exception_contains_id(): void
    {
        $e = new UserNotFoundException(77);
        $this->assertStringContainsString('77', $e->getMessage());
    }

    // ──────────────────────────────────────────────────────────────────────
    // UpdateProfileData DTO
    // ──────────────────────────────────────────────────────────────────────

    public function test_update_profile_data_from_array(): void
    {
        $dto = UpdateProfileData::fromArray(['name' => 'Dave', 'phone' => '+44777888999']);
        $this->assertEquals('Dave', $dto->name);
        $this->assertEquals('+44777888999', $dto->phone);
    }

    public function test_update_profile_data_phone_optional(): void
    {
        $dto = UpdateProfileData::fromArray(['name' => 'Dave']);
        $this->assertEquals('Dave', $dto->name);
        $this->assertNull($dto->phone);
    }
}
