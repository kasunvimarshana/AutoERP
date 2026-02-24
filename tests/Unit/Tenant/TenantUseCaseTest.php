<?php

namespace Tests\Unit\Tenant;

use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Mockery;
use Modules\Tenant\Application\UseCases\CreateTenantUseCase;
use Modules\Tenant\Application\UseCases\SuspendTenantUseCase;
use Modules\Tenant\Domain\Contracts\TenantRepositoryInterface;
use Modules\Tenant\Domain\Events\TenantCreated;
use Modules\Tenant\Domain\Events\TenantSuspended;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Tenant module use cases.
 *
 * Covers tenant creation with default field assignment and slug generation,
 * and tenant suspension with domain event dispatch.
 *
 * TenantCreated/TenantSuspended use the Dispatchable trait which calls
 * app(Dispatcher::class) directly rather than going through the Event facade.
 * We therefore register a dispatcher mock in the Container so that call resolves
 * to our mock, and set expectations directly on that mock.
 */
class TenantUseCaseTest extends TestCase
{
    private \Mockery\MockInterface $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dispatcher = Mockery::mock(Dispatcher::class);
        Container::getInstance()->instance(Dispatcher::class, $this->dispatcher);
        Container::getInstance()->instance('events', $this->dispatcher);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeTenant(string $status = 'active'): object
    {
        return (object) [
            'id'               => 'tenant-uuid-1',
            'name'             => 'Acme Corp',
            'slug'             => 'acme-corp',
            'status'           => $status,
            'timezone'         => 'UTC',
            'default_currency' => 'USD',
            'locale'           => 'en',
        ];
    }

    // -------------------------------------------------------------------------
    // CreateTenantUseCase
    // -------------------------------------------------------------------------

    public function test_create_tenant_sets_active_status_and_dispatches_event(): void
    {
        $tenant     = $this->makeTenant();
        $tenantRepo = Mockery::mock(TenantRepositoryInterface::class);

        $tenantRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['status'] === 'active'
                && $data['name'] === 'Acme Corp'
                && $data['timezone'] === 'UTC'
                && $data['default_currency'] === 'USD'
                && $data['locale'] === 'en')
            ->andReturn($tenant);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        $this->dispatcher->shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof TenantCreated
                && $e->tenantId   === 'tenant-uuid-1'
                && $e->tenantName === 'Acme Corp');

        $useCase = new CreateTenantUseCase($tenantRepo);
        $result  = $useCase->execute([
            'name' => 'Acme Corp',
        ]);

        $this->assertSame('active', $result->status);
        $this->assertSame('acme-corp', $result->slug);
    }

    public function test_create_tenant_applies_custom_timezone_and_currency(): void
    {
        $tenant = (object) [
            'id'               => 'tenant-uuid-2',
            'name'             => 'Global Ltd',
            'slug'             => 'global-ltd',
            'status'           => 'active',
            'timezone'         => 'Asia/Colombo',
            'default_currency' => 'LKR',
            'locale'           => 'si',
        ];
        $tenantRepo = Mockery::mock(TenantRepositoryInterface::class);

        $tenantRepo->shouldReceive('create')
            ->once()
            ->withArgs(fn ($data) => $data['timezone'] === 'Asia/Colombo'
                && $data['default_currency'] === 'LKR'
                && $data['locale'] === 'si')
            ->andReturn($tenant);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        $this->dispatcher->shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof TenantCreated);

        $useCase = new CreateTenantUseCase($tenantRepo);
        $result  = $useCase->execute([
            'name'             => 'Global Ltd',
            'timezone'         => 'Asia/Colombo',
            'default_currency' => 'LKR',
            'locale'           => 'si',
        ]);

        $this->assertSame('Asia/Colombo', $result->timezone);
        $this->assertSame('LKR', $result->default_currency);
    }

    // -------------------------------------------------------------------------
    // SuspendTenantUseCase
    // -------------------------------------------------------------------------

    public function test_suspend_tenant_calls_repository_and_dispatches_event(): void
    {
        $tenantRepo = Mockery::mock(TenantRepositoryInterface::class);

        $tenantRepo->shouldReceive('suspend')
            ->once()
            ->with('tenant-uuid-1')
            ->andReturn(true);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        $this->dispatcher->shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof TenantSuspended
                && $e->tenantId === 'tenant-uuid-1');

        $useCase = new SuspendTenantUseCase($tenantRepo);
        $result  = $useCase->execute(['tenant_id' => 'tenant-uuid-1']);

        $this->assertTrue($result);
    }

    // -------------------------------------------------------------------------
    // CreateTenantUseCase — guards
    // -------------------------------------------------------------------------

    public function test_create_tenant_throws_when_name_is_empty(): void
    {
        $tenantRepo = Mockery::mock(TenantRepositoryInterface::class);
        $tenantRepo->shouldNotReceive('create');

        $useCase = new CreateTenantUseCase($tenantRepo);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Tenant name is required.');

        $useCase->execute(['name' => '']);
    }

    public function test_create_tenant_throws_when_name_is_whitespace_only(): void
    {
        $tenantRepo = Mockery::mock(TenantRepositoryInterface::class);
        $tenantRepo->shouldNotReceive('create');

        $useCase = new CreateTenantUseCase($tenantRepo);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Tenant name is required.');

        $useCase->execute(['name' => '   ']);
    }
}
