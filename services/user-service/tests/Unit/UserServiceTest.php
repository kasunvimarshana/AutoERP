<?php

namespace Tests\Unit;

use App\DTOs\UserDTO;
use App\Events\UserCreated;
use App\Events\UserDeleted;
use App\Events\UserUpdated;
use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\UserService;
use App\Webhooks\WebhookDispatcher;
use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserRepositoryInterface $repository;
    private WebhookDispatcher       $dispatcher;
    private UserService             $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(UserRepositoryInterface::class);
        $this->dispatcher = Mockery::mock(WebhookDispatcher::class);
        $this->service    = new UserService($this->repository, $this->dispatcher);
    }

    /*
    |--------------------------------------------------------------------------
    | createUser
    |--------------------------------------------------------------------------
    */

    public function test_create_user_fires_event_and_dispatches_webhook(): void
    {
        Event::fake([UserCreated::class]);

        $dto  = $this->makeDto();
        $user = $this->fakeUser($dto);

        $this->repository
            ->shouldReceive('findByEmail')
            ->once()
            ->with($dto->email, $dto->tenantId)
            ->andReturn(null);

        $this->repository
            ->shouldReceive('create')
            ->once()
            ->with($dto)
            ->andReturn($user);

        $this->dispatcher
            ->shouldReceive('dispatch')
            ->once()
            ->with('user.created', (string) $dto->tenantId, Mockery::any());

        $result = $this->service->createUser($dto);

        $this->assertSame($user, $result);
        Event::assertDispatched(UserCreated::class);
    }

    public function test_create_user_throws_on_duplicate_email(): void
    {
        $dto      = $this->makeDto();
        $existing = $this->fakeUser($dto);

        $this->repository
            ->shouldReceive('findByEmail')
            ->once()
            ->andReturn($existing);

        $this->expectException(DomainException::class);

        $this->service->createUser($dto);
    }

    /*
    |--------------------------------------------------------------------------
    | updateUser
    |--------------------------------------------------------------------------
    */

    public function test_update_user_fires_event(): void
    {
        Event::fake([UserUpdated::class]);

        $dto  = $this->makeDto(id: 1);
        $user = $this->fakeUser($dto, id: 1);

        $this->repository->shouldReceive('findById')->once()->andReturn($user);
        $this->repository->shouldReceive('update')->once()->andReturn($user);

        $this->dispatcher->shouldReceive('dispatch')->once();

        $result = $this->service->updateUser(1, 1, $dto);

        $this->assertSame($user, $result);
        Event::assertDispatched(UserUpdated::class);
    }

    public function test_update_user_throws_when_not_found(): void
    {
        $dto = $this->makeDto();

        $this->repository->shouldReceive('findById')->once()->andReturn(null);

        $this->expectException(ModelNotFoundException::class);

        $this->service->updateUser(999, 1, $dto);
    }

    /*
    |--------------------------------------------------------------------------
    | deleteUser
    |--------------------------------------------------------------------------
    */

    public function test_delete_user_fires_event(): void
    {
        Event::fake([UserDeleted::class]);

        $dto  = $this->makeDto(id: 5);
        $user = $this->fakeUser($dto, id: 5);

        $this->repository->shouldReceive('findById')->once()->andReturn($user);
        $this->repository->shouldReceive('delete')->once()->andReturn(true);
        $this->dispatcher->shouldReceive('dispatch')->once();

        $result = $this->service->deleteUser(5, 1);

        $this->assertTrue($result);
        Event::assertDispatched(UserDeleted::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function makeDto(int $id = null): UserDTO
    {
        return new UserDTO(
            tenantId:    1,
            keycloakId:  null,
            name:        'Test User',
            email:       'test@example.com',
            username:    'testuser',
            role:        'staff',
            status:      'active',
            profile:     [],
            permissions: [],
            metadata:    [],
            id:          $id,
        );
    }

    private function fakeUser(UserDTO $dto, int $id = 1): User
    {
        $user              = new User($dto->toArray());
        $user->id          = $id;
        $user->tenant_id   = $dto->tenantId;
        $user->email       = $dto->email;
        $user->name        = $dto->name;
        $user->role        = $dto->role;

        return $user;
    }
}
