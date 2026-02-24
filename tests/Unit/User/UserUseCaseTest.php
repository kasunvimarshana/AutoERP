<?php

namespace Tests\Unit\User;

use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Modules\User\Application\UseCases\AssignRoleUseCase;
use Modules\User\Application\UseCases\CreateUserUseCase;
use Modules\User\Application\UseCases\InviteUserUseCase;
use Modules\User\Application\UseCases\UpdateUserProfileUseCase;
use Modules\User\Domain\Contracts\UserRepositoryInterface;
use Modules\User\Domain\Events\UserCreated;
use Modules\User\Domain\Events\UserInvited;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for User module use cases.
 *
 * Covers user creation with tenant scoping, user invitation with
 * pending-verification status, profile updates, and role assignment.
 *
 * UserCreated/UserInvited use the Dispatchable trait which calls
 * app(Dispatcher::class) directly rather than going through the Event facade.
 * We therefore register a dispatcher mock in the Container so that call resolves
 * to our mock, and set expectations directly on that mock.
 */
class UserUseCaseTest extends TestCase
{
    private \Mockery\MockInterface $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dispatcher = Mockery::mock(Dispatcher::class);
        Container::getInstance()->instance(Dispatcher::class, $this->dispatcher);
        Container::getInstance()->instance('events', $this->dispatcher);
        // InviteUserUseCase calls Hash::make() — mock the facade so it
        // resolves without a full Laravel application being bootstrapped.
        Hash::shouldReceive('make')->andReturn('hashed-password')->byDefault();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeUser(string $status = 'active'): object
    {
        return (object) [
            'id'        => 'user-uuid-1',
            'tenant_id' => 'tenant-uuid-1',
            'name'      => 'Jane Doe',
            'email'     => 'jane@example.com',
            'status'    => $status,
        ];
    }

    // -------------------------------------------------------------------------
    // CreateUserUseCase
    // -------------------------------------------------------------------------

    public function test_create_user_sets_active_status_and_dispatches_event(): void
    {
        $user     = $this->makeUser();
        $userRepo = Mockery::mock(UserRepositoryInterface::class);

        $userRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['status'] === 'active'
                && $data['tenant_id'] === 'tenant-uuid-1'
                && $data['email'] === 'jane@example.com')
            ->andReturn($user);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        $this->dispatcher->shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof UserCreated
                && $e->userId === 'user-uuid-1');

        $useCase = new CreateUserUseCase($userRepo);
        $result  = $useCase->execute([
            'tenant_id' => 'tenant-uuid-1',
            'name'      => 'Jane Doe',
            'email'     => 'jane@example.com',
            'password'  => 'hashed-password',
        ]);

        $this->assertSame('active', $result->status);
        $this->assertSame('user-uuid-1', $result->id);
    }

    // -------------------------------------------------------------------------
    // InviteUserUseCase
    // -------------------------------------------------------------------------

    public function test_invite_user_sets_pending_verification_and_dispatches_event(): void
    {
        $pending  = $this->makeUser('pending_verification');
        $userRepo = Mockery::mock(UserRepositoryInterface::class);

        $userRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['status'] === 'pending_verification'
                && $data['email'] === 'bob@example.com'
                && isset($data['invited_by']))
            ->andReturn($pending);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        $this->dispatcher->shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof UserInvited
                && $e->userId === 'user-uuid-1');

        $useCase = new InviteUserUseCase($userRepo);
        $result  = $useCase->execute([
            'tenant_id'  => 'tenant-uuid-1',
            'name'       => 'Bob Smith',
            'email'      => 'bob@example.com',
            'invited_by' => 'user-uuid-1',
        ]);

        $this->assertSame('pending_verification', $result->status);
    }

    // -------------------------------------------------------------------------
    // UpdateUserProfileUseCase
    // -------------------------------------------------------------------------

    public function test_update_profile_applies_name_change(): void
    {
        $updated  = (object) ['id' => 'user-uuid-1', 'name' => 'Jane Smith'];
        $userRepo = Mockery::mock(UserRepositoryInterface::class);

        $userRepo->shouldReceive('update')
            ->once()
            ->withArgs(fn ($id, $data) => $id === 'user-uuid-1'
                && $data['name'] === 'Jane Smith')
            ->andReturn($updated);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new UpdateUserProfileUseCase($userRepo);
        $result  = $useCase->execute([
            'id'   => 'user-uuid-1',
            'name' => 'Jane Smith',
        ]);

        $this->assertSame('Jane Smith', $result->name);
    }

    public function test_update_profile_excludes_null_fields(): void
    {
        $updated  = (object) ['id' => 'user-uuid-1', 'name' => 'Jane Doe'];
        $userRepo = Mockery::mock(UserRepositoryInterface::class);

        $userRepo->shouldReceive('update')
            ->once()
            ->withArgs(fn ($id, $data) => !array_key_exists('avatar_path', $data))
            ->andReturn($updated);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $useCase = new UpdateUserProfileUseCase($userRepo);
        $useCase->execute([
            'id'   => 'user-uuid-1',
            'name' => 'Jane Doe',
            // avatar_path intentionally omitted — must not be passed as null
        ]);
        // Mockery verifies ->once() in tearDown; count the expectation as an assertion.
        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // AssignRoleUseCase
    // -------------------------------------------------------------------------

    public function test_assign_role_delegates_to_repository(): void
    {
        $userRepo = Mockery::mock(UserRepositoryInterface::class);

        $userRepo->shouldReceive('assignRole')
            ->once()
            ->with('user-uuid-1', 'role-uuid-1');

        $useCase = new AssignRoleUseCase($userRepo);
        $useCase->execute(['user_id' => 'user-uuid-1', 'role_id' => 'role-uuid-1']);
        // Mockery verifies ->once() in tearDown; count the expectation as an assertion.
        $this->addToAssertionCount(1);
    }
}
