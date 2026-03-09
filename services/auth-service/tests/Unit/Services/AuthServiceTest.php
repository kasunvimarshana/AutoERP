<?php

namespace Tests\Unit\Services;

use App\Domain\Contracts\TenantRepositoryInterface;
use App\Domain\Contracts\UserRepositoryInterface;
use App\Domain\Models\Tenant;
use App\Domain\Models\User;
use App\Services\AuthService;
use App\Services\TokenService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    private AuthService $service;
    private MockInterface $userRepo;
    private MockInterface $tenantRepo;
    private MockInterface $tokenService;
    private Tenant $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepo     = Mockery::mock(UserRepositoryInterface::class);
        $this->tenantRepo   = Mockery::mock(TenantRepositoryInterface::class);
        $this->tokenService = Mockery::mock(TokenService::class);

        $this->service = new AuthService(
            $this->userRepo,
            $this->tenantRepo,
            $this->tokenService,
        );

        $this->tenant = new Tenant(['id' => 'tenant-uuid', 'status' => 'active', 'subdomain' => 'acme']);
        $this->tenant->plan = 'pro';

        $this->user = new User([
            'id'        => 'user-uuid',
            'email'     => 'user@example.com',
            'password'  => Hash::make('ValidPass@123'),
            'is_active' => true,
            'tenant_id' => 'tenant-uuid',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function login_returns_token_for_valid_credentials(): void
    {
        $this->tenantRepo->shouldReceive('findOrFail')
            ->once()
            ->with('tenant-uuid')
            ->andReturn($this->tenant);

        $this->userRepo->shouldReceive('findByEmail')
            ->once()
            ->with('user@example.com', 'tenant-uuid')
            ->andReturn($this->user);

        $this->tokenService->shouldReceive('createForUser')
            ->once()
            ->andReturn([
                'access_token'  => 'test-access-token',
                'refresh_token' => null,
                'expires_in'    => 86400,
                'token_model'   => null,
            ]);

        // Mock the user's recordLogin method
        $this->user->setRelation('tenant', $this->tenant);

        $result = $this->service->login(
            email: 'user@example.com',
            password: 'ValidPass@123',
            tenantId: 'tenant-uuid',
        );

        $this->assertEquals('test-access-token', $result['access_token']);
        $this->assertInstanceOf(User::class, $result['user']);
    }

    /** @test */
    public function login_throws_exception_for_wrong_password(): void
    {
        $this->expectException(ValidationException::class);

        $this->tenantRepo->shouldReceive('findOrFail')
            ->once()
            ->andReturn($this->tenant);

        $this->userRepo->shouldReceive('findByEmail')
            ->once()
            ->andReturn($this->user);

        $this->service->login(
            email: 'user@example.com',
            password: 'WrongPassword!',
            tenantId: 'tenant-uuid',
        );
    }

    /** @test */
    public function login_throws_exception_for_unknown_email(): void
    {
        $this->expectException(ValidationException::class);

        $this->tenantRepo->shouldReceive('findOrFail')
            ->once()
            ->andReturn($this->tenant);

        $this->userRepo->shouldReceive('findByEmail')
            ->once()
            ->andReturn(null);

        $this->service->login(
            email: 'nobody@example.com',
            password: 'ValidPass@123',
            tenantId: 'tenant-uuid',
        );
    }

    /** @test */
    public function login_throws_exception_for_inactive_user(): void
    {
        $this->expectException(ValidationException::class);

        $this->user->is_active = false;

        $this->tenantRepo->shouldReceive('findOrFail')
            ->once()
            ->andReturn($this->tenant);

        $this->userRepo->shouldReceive('findByEmail')
            ->once()
            ->andReturn($this->user);

        $this->service->login(
            email: 'user@example.com',
            password: 'ValidPass@123',
            tenantId: 'tenant-uuid',
        );
    }

    /** @test */
    public function login_throws_exception_for_suspended_tenant(): void
    {
        $this->expectException(ValidationException::class);

        $this->tenant->status = 'suspended';

        $this->tenantRepo->shouldReceive('findOrFail')
            ->once()
            ->andReturn($this->tenant);

        $this->service->login(
            email: 'user@example.com',
            password: 'ValidPass@123',
            tenantId: 'tenant-uuid',
        );
    }

    /** @test */
    public function register_creates_user_and_returns_token(): void
    {
        $this->tenant->setRelation('users', collect([]));

        $this->tenantRepo->shouldReceive('findOrFail')
            ->once()
            ->andReturn($this->tenant);

        // Mock plan limit check — use a real tenant that passes
        $tenant = Mockery::mock(Tenant::class)->makePartial();
        $tenant->id        = 'tenant-uuid';
        $tenant->status    = 'active';
        $tenant->plan      = 'pro';
        $tenant->subdomain = 'acme';
        $tenant->shouldReceive('withinPlanLimit')->andReturn(true);
        $tenant->shouldReceive('users->count')->andReturn(0);

        $this->tenantRepo->shouldReceive('findOrFail')
            ->andReturn($tenant);

        $newUser = new User([
            'id'        => 'new-user-uuid',
            'email'     => 'new@example.com',
            'tenant_id' => 'tenant-uuid',
        ]);

        $this->userRepo->shouldReceive('create')
            ->once()
            ->andReturn($newUser);

        $newUser->setRelation('tenant', $tenant);

        $this->tokenService->shouldReceive('createForUser')
            ->once()
            ->andReturn([
                'access_token'  => 'new-access-token',
                'refresh_token' => null,
                'expires_in'    => 86400,
                'token_model'   => null,
            ]);

        $result = $this->service->register([
            'tenant_id' => 'tenant-uuid',
            'name'      => 'New User',
            'email'     => 'new@example.com',
            'password'  => 'StrongPass@2024!',
        ]);

        $this->assertEquals('new-access-token', $result['access_token']);
    }
}
