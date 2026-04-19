<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\Passport;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Finance\Application\Contracts\CreateFiscalPeriodServiceInterface;
use Modules\Finance\Application\Contracts\CreateFiscalYearServiceInterface;
use Modules\Finance\Application\Contracts\DeleteFiscalPeriodServiceInterface;
use Modules\Finance\Application\Contracts\DeleteFiscalYearServiceInterface;
use Modules\Finance\Application\Contracts\FindFiscalPeriodServiceInterface;
use Modules\Finance\Application\Contracts\FindFiscalYearServiceInterface;
use Modules\Finance\Application\Contracts\UpdateFiscalPeriodServiceInterface;
use Modules\Finance\Application\Contracts\UpdateFiscalYearServiceInterface;
use Modules\Finance\Domain\Entities\FiscalPeriod;
use Modules\Finance\Domain\Entities\FiscalYear;
use Modules\Finance\Domain\Exceptions\FiscalPeriodAlreadyExistsException;
use Modules\Finance\Domain\Exceptions\FiscalYearAlreadyExistsException;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class FinanceFiscalEndpointsAuthenticatedTest extends TestCase
{
    /** @var CreateFiscalYearServiceInterface&MockObject */
    private CreateFiscalYearServiceInterface $createFiscalYearService;

    /** @var UpdateFiscalYearServiceInterface&MockObject */
    private UpdateFiscalYearServiceInterface $updateFiscalYearService;

    /** @var FindFiscalYearServiceInterface&MockObject */
    private FindFiscalYearServiceInterface $findFiscalYearService;

    /** @var CreateFiscalPeriodServiceInterface&MockObject */
    private CreateFiscalPeriodServiceInterface $createFiscalPeriodService;

    /** @var UpdateFiscalPeriodServiceInterface&MockObject */
    private UpdateFiscalPeriodServiceInterface $updateFiscalPeriodService;

    /** @var FindFiscalPeriodServiceInterface&MockObject */
    private FindFiscalPeriodServiceInterface $findFiscalPeriodService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureReferenceTables();
        $this->seedReferenceRows();

        $this->createFiscalYearService = $this->createMock(CreateFiscalYearServiceInterface::class);
        $this->updateFiscalYearService = $this->createMock(UpdateFiscalYearServiceInterface::class);
        $this->findFiscalYearService = $this->createMock(FindFiscalYearServiceInterface::class);

        $this->createFiscalPeriodService = $this->createMock(CreateFiscalPeriodServiceInterface::class);
        $this->updateFiscalPeriodService = $this->createMock(UpdateFiscalPeriodServiceInterface::class);
        $this->findFiscalPeriodService = $this->createMock(FindFiscalPeriodServiceInterface::class);

        $this->app->instance(CreateFiscalYearServiceInterface::class, $this->createFiscalYearService);
        $this->app->instance(UpdateFiscalYearServiceInterface::class, $this->updateFiscalYearService);
        $this->app->instance(FindFiscalYearServiceInterface::class, $this->findFiscalYearService);
        $this->app->instance(DeleteFiscalYearServiceInterface::class, $this->createMock(DeleteFiscalYearServiceInterface::class));

        $this->app->instance(CreateFiscalPeriodServiceInterface::class, $this->createFiscalPeriodService);
        $this->app->instance(UpdateFiscalPeriodServiceInterface::class, $this->updateFiscalPeriodService);
        $this->app->instance(FindFiscalPeriodServiceInterface::class, $this->findFiscalPeriodService);
        $this->app->instance(DeleteFiscalPeriodServiceInterface::class, $this->createMock(DeleteFiscalPeriodServiceInterface::class));

        $authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $authorizationService->method('can')->willReturn(true);
        $this->app->instance(AuthorizationServiceInterface::class, $authorizationService);

        $tenantConfigClient = $this->createMock(TenantConfigClientInterface::class);
        $tenantConfigClient->method('getConfig')->willReturn(null);
        $this->app->instance(TenantConfigClientInterface::class, $tenantConfigClient);

        $tenantConfigManager = $this->createMock(TenantConfigManagerInterface::class);
        $this->app->instance(TenantConfigManagerInterface::class, $tenantConfigManager);

        $user = new UserModel([
            'id' => 311,
            'tenant_id' => 9,
            'email' => 'finance.fiscal.test@example.com',
            'password' => 'secret',
            'first_name' => 'Finance',
            'last_name' => 'FiscalTester',
        ]);
        $user->setAttribute('id', 311);
        $user->setAttribute('tenant_id', 9);

        Passport::actingAs($user, [], 'api');
    }

    public function test_authenticated_store_fiscal_year_returns_conflict_when_duplicate(): void
    {
        $this->createFiscalYearService
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static fn (array $payload): bool =>
                $payload['tenant_id'] === 9 && $payload['name'] === 'FY2026'
            ))
            ->willThrowException(new FiscalYearAlreadyExistsException(9, 'FY2026'));

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/fiscal-years', [
                'tenant_id' => 9,
                'name' => 'FY2026',
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'status' => 'open',
            ]);

        $response->assertStatus(HttpResponse::HTTP_CONFLICT)
            ->assertJsonPath('message', 'Fiscal year "FY2026" already exists for tenant 9.');
    }

    public function test_authenticated_index_fiscal_year_returns_paginated_payload(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [
                $this->buildFiscalYear(72, 'FY2027'),
            ],
            total: 1,
            perPage: 15,
            currentPage: 1,
        );

        $this->findFiscalYearService
            ->expects($this->once())
            ->method('list')
            ->with(
                [
                    'tenant_id' => 9,
                    'name' => 'FY2027',
                    'status' => 'open',
                ],
                15,
                1,
                '-created_at',
            )
            ->willReturn($paginator);

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/fiscal-years?tenant_id=9&name=FY2027&status=open&sort=-created_at');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 72)
            ->assertJsonPath('data.0.tenant_id', 9)
            ->assertJsonPath('data.0.name', 'FY2027')
            ->assertJsonStructure([
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_authenticated_show_fiscal_year_returns_resource_payload(): void
    {
        $this->findFiscalYearService
            ->expects($this->once())
            ->method('find')
            ->with(72)
            ->willReturn($this->buildFiscalYear(72, 'FY2027'));

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/fiscal-years/72');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 72)
            ->assertJsonPath('data.tenant_id', 9)
            ->assertJsonPath('data.name', 'FY2027')
            ->assertJsonPath('data.start_date', '2026-01-01')
            ->assertJsonPath('data.end_date', '2026-12-31')
            ->assertJsonPath('data.status', 'open');
    }

    public function test_authenticated_show_fiscal_year_returns_not_found_when_missing(): void
    {
        $this->findFiscalYearService
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/fiscal-years/999');

        $response->assertStatus(HttpResponse::HTTP_NOT_FOUND)
            ->assertJsonPath('message', 'Fiscal year not found.');
    }

    public function test_authenticated_store_fiscal_year_returns_created_resource_payload(): void
    {
        $this->createFiscalYearService
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static fn (array $payload): bool =>
                $payload['tenant_id'] === 9
                && $payload['name'] === 'FY2027'
                && $payload['start_date'] === '2027-01-01'
                && $payload['end_date'] === '2027-12-31'
            ))
            ->willReturn(new FiscalYear(
                tenantId: 9,
                name: 'FY2027',
                startDate: new \DateTimeImmutable('2027-01-01'),
                endDate: new \DateTimeImmutable('2027-12-31'),
                status: 'open',
                id: 72,
            ));

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/fiscal-years', [
                'tenant_id' => 9,
                'name' => 'FY2027',
                'start_date' => '2027-01-01',
                'end_date' => '2027-12-31',
                'status' => 'open',
            ]);

        $response->assertStatus(HttpResponse::HTTP_CREATED)
            ->assertJsonPath('data.id', 72)
            ->assertJsonPath('data.tenant_id', 9)
            ->assertJsonPath('data.name', 'FY2027')
            ->assertJsonPath('data.start_date', '2027-01-01')
            ->assertJsonPath('data.end_date', '2027-12-31')
            ->assertJsonPath('data.status', 'open');
    }

    public function test_authenticated_update_fiscal_year_returns_conflict_when_duplicate(): void
    {
        $this->findFiscalYearService
            ->expects($this->once())
            ->method('find')
            ->with(71)
            ->willReturn($this->buildFiscalYear(71));

        $this->updateFiscalYearService
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static fn (array $payload): bool =>
                $payload['id'] === 71 && $payload['tenant_id'] === 9 && $payload['name'] === 'FY2026'
            ))
            ->willThrowException(new FiscalYearAlreadyExistsException(9, 'FY2026'));

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->putJson('/api/fiscal-years/71', [
                'tenant_id' => 9,
                'name' => 'FY2026',
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'status' => 'open',
            ]);

        $response->assertStatus(HttpResponse::HTTP_CONFLICT)
            ->assertJsonPath('message', 'Fiscal year "FY2026" already exists for tenant 9.');
    }

    public function test_authenticated_update_fiscal_year_returns_resource_payload(): void
    {
        $this->findFiscalYearService
            ->expects($this->once())
            ->method('find')
            ->with(71)
            ->willReturn($this->buildFiscalYear(71));

        $this->updateFiscalYearService
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static fn (array $payload): bool =>
                $payload['id'] === 71
                && $payload['tenant_id'] === 9
                && $payload['name'] === 'FY2026 Revised'
            ))
            ->willReturn(new FiscalYear(
                tenantId: 9,
                name: 'FY2026 Revised',
                startDate: new \DateTimeImmutable('2026-01-01'),
                endDate: new \DateTimeImmutable('2026-12-31'),
                status: 'open',
                id: 71,
            ));

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->putJson('/api/fiscal-years/71', [
                'tenant_id' => 9,
                'name' => 'FY2026 Revised',
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'status' => 'open',
            ]);

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 71)
            ->assertJsonPath('data.tenant_id', 9)
            ->assertJsonPath('data.name', 'FY2026 Revised')
            ->assertJsonPath('data.start_date', '2026-01-01')
            ->assertJsonPath('data.end_date', '2026-12-31')
            ->assertJsonPath('data.status', 'open');
    }

    public function test_authenticated_store_fiscal_period_returns_conflict_when_duplicate(): void
    {
        $this->createFiscalPeriodService
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static fn (array $payload): bool =>
                $payload['tenant_id'] === 9
                && $payload['fiscal_year_id'] === 100
                && $payload['period_number'] === 1
            ))
            ->willThrowException(new FiscalPeriodAlreadyExistsException(9, 100, 1));

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/fiscal-periods', [
                'tenant_id' => 9,
                'fiscal_year_id' => 100,
                'period_number' => 1,
                'name' => 'P1',
                'start_date' => '2026-01-01',
                'end_date' => '2026-01-31',
                'status' => 'open',
            ]);

        $response->assertStatus(HttpResponse::HTTP_CONFLICT)
            ->assertJsonPath('message', 'Fiscal period number 1 already exists for tenant 9 in fiscal year 100.');
    }

    public function test_authenticated_index_fiscal_period_returns_paginated_payload(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [
                $this->buildFiscalPeriod(56, 2, 'P2'),
            ],
            total: 1,
            perPage: 15,
            currentPage: 1,
        );

        $this->findFiscalPeriodService
            ->expects($this->once())
            ->method('list')
            ->with(
                [
                    'tenant_id' => 9,
                    'fiscal_year_id' => 100,
                    'period_number' => 2,
                    'name' => 'P2',
                    'status' => 'open',
                ],
                15,
                1,
                '-created_at',
            )
            ->willReturn($paginator);

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/fiscal-periods?tenant_id=9&fiscal_year_id=100&period_number=2&name=P2&status=open&sort=-created_at');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 56)
            ->assertJsonPath('data.0.tenant_id', 9)
            ->assertJsonPath('data.0.fiscal_year_id', 100)
            ->assertJsonPath('data.0.period_number', 2)
            ->assertJsonPath('data.0.name', 'P2')
            ->assertJsonStructure([
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_authenticated_show_fiscal_period_returns_resource_payload(): void
    {
        $this->findFiscalPeriodService
            ->expects($this->once())
            ->method('find')
            ->with(56)
            ->willReturn($this->buildFiscalPeriod(56, 2, 'P2'));

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/fiscal-periods/56');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 56)
            ->assertJsonPath('data.tenant_id', 9)
            ->assertJsonPath('data.fiscal_year_id', 100)
            ->assertJsonPath('data.period_number', 2)
            ->assertJsonPath('data.name', 'P2')
            ->assertJsonPath('data.start_date', '2026-01-01')
            ->assertJsonPath('data.end_date', '2026-01-31')
            ->assertJsonPath('data.status', 'open');
    }

    public function test_authenticated_show_fiscal_period_returns_not_found_when_missing(): void
    {
        $this->findFiscalPeriodService
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->getJson('/api/fiscal-periods/999');

        $response->assertStatus(HttpResponse::HTTP_NOT_FOUND)
            ->assertJsonPath('message', 'Fiscal period not found.');
    }

    public function test_authenticated_store_fiscal_period_returns_created_resource_payload(): void
    {
        $this->createFiscalPeriodService
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static fn (array $payload): bool =>
                $payload['tenant_id'] === 9
                && $payload['fiscal_year_id'] === 100
                && $payload['period_number'] === 2
            ))
            ->willReturn(new FiscalPeriod(
                tenantId: 9,
                fiscalYearId: 100,
                periodNumber: 2,
                name: 'P2',
                startDate: new \DateTimeImmutable('2026-02-01'),
                endDate: new \DateTimeImmutable('2026-02-28'),
                status: 'open',
                id: 56,
            ));

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->postJson('/api/fiscal-periods', [
                'tenant_id' => 9,
                'fiscal_year_id' => 100,
                'period_number' => 2,
                'name' => 'P2',
                'start_date' => '2026-02-01',
                'end_date' => '2026-02-28',
                'status' => 'open',
            ]);

        $response->assertStatus(HttpResponse::HTTP_CREATED)
            ->assertJsonPath('data.id', 56)
            ->assertJsonPath('data.tenant_id', 9)
            ->assertJsonPath('data.fiscal_year_id', 100)
            ->assertJsonPath('data.period_number', 2)
            ->assertJsonPath('data.name', 'P2')
            ->assertJsonPath('data.start_date', '2026-02-01')
            ->assertJsonPath('data.end_date', '2026-02-28')
            ->assertJsonPath('data.status', 'open');
    }

    public function test_authenticated_update_fiscal_period_returns_conflict_when_duplicate(): void
    {
        $this->findFiscalPeriodService
            ->expects($this->once())
            ->method('find')
            ->with(55)
            ->willReturn($this->buildFiscalPeriod(55));

        $this->updateFiscalPeriodService
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static fn (array $payload): bool =>
                $payload['id'] === 55
                && $payload['tenant_id'] === 9
                && $payload['fiscal_year_id'] === 100
                && $payload['period_number'] === 1
            ))
            ->willThrowException(new FiscalPeriodAlreadyExistsException(9, 100, 1));

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->putJson('/api/fiscal-periods/55', [
                'tenant_id' => 9,
                'fiscal_year_id' => 100,
                'period_number' => 1,
                'name' => 'P1',
                'start_date' => '2026-01-01',
                'end_date' => '2026-01-31',
                'status' => 'open',
            ]);

        $response->assertStatus(HttpResponse::HTTP_CONFLICT)
            ->assertJsonPath('message', 'Fiscal period number 1 already exists for tenant 9 in fiscal year 100.');
    }

    public function test_authenticated_update_fiscal_period_returns_resource_payload(): void
    {
        $this->findFiscalPeriodService
            ->expects($this->once())
            ->method('find')
            ->with(55)
            ->willReturn($this->buildFiscalPeriod(55));

        $this->updateFiscalPeriodService
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(static fn (array $payload): bool =>
                $payload['id'] === 55
                && $payload['tenant_id'] === 9
                && $payload['fiscal_year_id'] === 100
                && $payload['period_number'] === 2
            ))
            ->willReturn(new FiscalPeriod(
                tenantId: 9,
                fiscalYearId: 100,
                periodNumber: 2,
                name: 'P2 Revised',
                startDate: new \DateTimeImmutable('2026-02-01'),
                endDate: new \DateTimeImmutable('2026-02-28'),
                status: 'open',
                id: 55,
            ));

        $response = $this->withHeader('X-Tenant-ID', '9')
            ->putJson('/api/fiscal-periods/55', [
                'tenant_id' => 9,
                'fiscal_year_id' => 100,
                'period_number' => 2,
                'name' => 'P2 Revised',
                'start_date' => '2026-02-01',
                'end_date' => '2026-02-28',
                'status' => 'open',
            ]);

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 55)
            ->assertJsonPath('data.tenant_id', 9)
            ->assertJsonPath('data.fiscal_year_id', 100)
            ->assertJsonPath('data.period_number', 2)
            ->assertJsonPath('data.name', 'P2 Revised')
            ->assertJsonPath('data.start_date', '2026-02-01')
            ->assertJsonPath('data.end_date', '2026-02-28')
            ->assertJsonPath('data.status', 'open');
    }

    private function buildFiscalYear(int $id, string $name = 'FY2026'): FiscalYear
    {
        return new FiscalYear(
            tenantId: 9,
            name: $name,
            startDate: new \DateTimeImmutable('2026-01-01'),
            endDate: new \DateTimeImmutable('2026-12-31'),
            status: 'open',
            id: $id,
        );
    }

    private function buildFiscalPeriod(int $id, int $periodNumber = 1, string $name = 'P1'): FiscalPeriod
    {
        return new FiscalPeriod(
            tenantId: 9,
            fiscalYearId: 100,
            periodNumber: $periodNumber,
            name: $name,
            startDate: new \DateTimeImmutable('2026-01-01'),
            endDate: new \DateTimeImmutable('2026-01-31'),
            status: 'open',
            id: $id,
        );
    }

    private function ensureReferenceTables(): void
    {
        if (! Schema::hasTable('tenants')) {
            Schema::create('tenants', function (Blueprint $table): void {
                $table->id();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('fiscal_years')) {
            Schema::create('fiscal_years', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->string('name');
                $table->date('start_date');
                $table->date('end_date');
                $table->string('status')->default('open');
                $table->timestamps();
            });
        }
    }

    private function seedReferenceRows(): void
    {
        if (! DB::table('tenants')->where('id', 9)->exists()) {
            DB::table('tenants')->insert([
                'id' => 9,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! DB::table('fiscal_years')->where('id', 100)->exists()) {
            DB::table('fiscal_years')->insert([
                'id' => 100,
                'tenant_id' => 9,
                'name' => 'FY2026',
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'status' => 'open',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
