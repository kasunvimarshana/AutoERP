<?php
declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Modules\User\Domain\Entities\User;
use Modules\User\Domain\Exceptions\UserNotFoundException;
use Modules\User\Domain\Exceptions\InvalidCredentialsException;
use Modules\User\Domain\RepositoryInterfaces\UserRepositoryInterface;
use Modules\User\Application\DTOs\CreateUserData;
use Modules\User\Application\DTOs\UpdateUserData;
use Modules\User\Application\Services\GetUserService;
use Modules\User\Application\Services\DeleteUserService;

class UserModuleTest extends TestCase
{
    private function makeUser(int $id = 1): User
    {
        return new User(
            $id, 1, 'John Doe', 'john@example.com', 'hashed_password',
            'active', '+1234567890', null, null, null,
            new \DateTime(), new \DateTime()
        );
    }

    public function test_user_entity_getters(): void
    {
        $user = $this->makeUser();
        $this->assertEquals(1, $user->getId());
        $this->assertEquals(1, $user->getTenantId());
        $this->assertEquals('John Doe', $user->getName());
        $this->assertEquals('john@example.com', $user->getEmail());
        $this->assertEquals('active', $user->getStatus());
        $this->assertTrue($user->isActive());
    }

    public function test_user_deactivate(): void
    {
        $user = $this->makeUser();
        $user->deactivate();
        $this->assertFalse($user->isActive());
        $this->assertEquals('inactive', $user->getStatus());
    }

    public function test_user_update_profile(): void
    {
        $user = $this->makeUser();
        $user->updateProfile('Jane Doe', '+9876543210');
        $this->assertEquals('Jane Doe', $user->getName());
        $this->assertEquals('+9876543210', $user->getPhone());
    }

    public function test_user_update_avatar(): void
    {
        $user = $this->makeUser();
        $user->updateAvatar('avatars/avatar.jpg');
        $this->assertEquals('avatars/avatar.jpg', $user->getAvatar());
    }

    public function test_get_user_service_finds_user(): void
    {
        /** @var UserRepositoryInterface&MockObject $repo */
        $repo = $this->createMock(UserRepositoryInterface::class);
        $repo->expects($this->once())->method('findById')->with(1)->willReturn($this->makeUser());

        $service = new GetUserService($repo);
        $result = $service->findById(1);
        $this->assertEquals(1, $result->getId());
    }

    public function test_get_user_service_throws_not_found(): void
    {
        /** @var UserRepositoryInterface&MockObject $repo */
        $repo = $this->createMock(UserRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $service = new GetUserService($repo);
        $this->expectException(UserNotFoundException::class);
        $service->findById(999);
    }

    public function test_delete_user_service_throws_not_found(): void
    {
        /** @var UserRepositoryInterface&MockObject $repo */
        $repo = $this->createMock(UserRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $service = new DeleteUserService($repo);
        $this->expectException(UserNotFoundException::class);
        $service->execute(999);
    }

    public function test_user_dto_from_array(): void
    {
        $data = CreateUserData::fromArray([
            'tenant_id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'secret123',
        ]);
        $this->assertEquals(1, $data->tenant_id);
        $this->assertEquals('Test User', $data->name);
        $this->assertEquals('test@example.com', $data->email);
        $this->assertEquals('active', $data->status);
    }

    public function test_user_not_found_exception_message(): void
    {
        $e = new UserNotFoundException(42);
        $this->assertStringContainsString('42', $e->getMessage());
        $this->assertStringContainsString('User', $e->getMessage());
    }

    public function test_invalid_credentials_exception(): void
    {
        $e = new InvalidCredentialsException();
        $this->assertEquals('Invalid credentials.', $e->getMessage());
    }
}
