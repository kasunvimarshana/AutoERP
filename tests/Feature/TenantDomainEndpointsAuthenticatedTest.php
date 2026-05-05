<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\PresenceVerifierInterface;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Tenant\Application\Contracts\CreateTenantDomainServiceInterface;
use Modules\Tenant\Application\Contracts\DeleteTenantDomainServiceInterface;
use Modules\Tenant\Application\Contracts\FindTenantDomainsServiceInterface;
use Modules\Tenant\Application\Contracts\FindTenantServiceInterface;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\Tenant\Application\Contracts\UpdateTenantDomainServiceInterface;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Domain\Entities\TenantDomain;
use Modules\Tenant\Domain\ValueObjects\DatabaseConfig;
use Modules\User\Application\Contracts\FindUserServiceInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class TenantDomainEndpointsAuthenticatedTest extends TestCase
{
    private static bool $routesCleared = false;

    private UserModel $authUser;

    /** @var FindTenantServiceInterface&MockObject */
    private FindTenantServiceInterface $findTenantService;

    /** @var FindTenantDomainsServiceInterface&MockObject */
    private FindTenantDomainsServiceInterface $findDomainsService;

    /** @var CreateTenantDomainServiceInterface&MockObject */
    private CreateTenantDomainServiceInterface $createDomainService;

    /** @var UpdateTenantDomainServiceInterface&MockObject */
    private UpdateTenantDomainServiceInterface $updateDomainService;

    /** @var DeleteTenantDomainServiceInterface&MockObject */
    private DeleteTenantDomainServiceInterface $deleteDomainService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clearRoutesCacheOnce();

        $this->authUser = new UserModel([
            'email' => 'domain.test@example.com',
            'password' => 'secret',
            'first_name' => 'Domain',
            'last_name' => 'Tester',
        ]);
        $this->authUser->setAttribute('id', 99);
        $this->authUser->setAttribute('tenant_id', 1);

        $tenantEntity = new Tenant(
            name: 'Test Corp',
            slug: 'test-corp',
            databaseConfig: new DatabaseConfig(
                driver: 'mysql',
                host: 'localhost',
                port: 3306,
                database: 'testdb',
                username: 'root',
                password: 'secret',
            ),
            id: 1,
        );

        $this->findTenantService = $this->createMock(FindTenantServiceInterface::class);
        $this->findTenantService->method('find')->willReturn($tenantEntity);
        $this->app->instance(FindTenantServiceInterface::class, $this->findTenantService);

        $this->findDomainsService = $this->createMock(FindTenantDomainsServiceInterface::class);
        $this->createDomainService = $this->createMock(CreateTenantDomainServiceInterface::class);
        $this->updateDomainService = $this->createMock(UpdateTenantDomainServiceInterface::class);
        $this->deleteDomainService = $this->createMock(DeleteTenantDomainServiceInterface::class);

        $this->app->instance(FindTenantDomainsServiceInterface::class, $this->findDomainsService);
        $this->app->instance(CreateTenantDomainServiceInterface::class, $this->createDomainService);
        $this->app->instance(UpdateTenantDomainServiceInterface::class, $this->updateDomainService);
        $this->app->instance(DeleteTenantDomainServiceInterface::class, $this->deleteDomainService);

        $authService = $this->createMock(AuthorizationServiceInterface::class);
        $authService->method('can')->willReturn(true);
        $this->app->instance(AuthorizationServiceInterface::class, $authService);

        $tenantConfigClient = $this->createMock(TenantConfigClientInterface::class);
        $tenantConfigClient->method('getConfig')->willReturn(null);
        $this->app->instance(TenantConfigClientInterface::class, $tenantConfigClient);
        $this->app->instance(TenantConfigManagerInterface::class, $this->createMock(TenantConfigManagerInterface::class));

        $this->app->instance(FindUserServiceInterface::class, $this->createMock(FindUserServiceInterface::class));

        $presenceVerifier = $this->createMock(PresenceVerifierInterface::class);
        $presenceVerifier->method('getCount')->willReturnCallback(
            static function (string $collection, string $column): int {
                if ($column === 'domain') {
                    return 0; // unique — domain not already taken
                }

                return 1; // exists
            }
        );
        $presenceVerifier->method('getMultiCount')->willReturn(1);
        $this->app->instance(PresenceVerifierInterface::class, $presenceVerifier);
        $this->app['validator']->setPresenceVerifier($presenceVerifier);

        $this->actingAs($this->authUser, (string) config('auth_context.guards.api', config('auth.defaults.guard', 'api')));
    }

    private function clearRoutesCacheOnce(): void
    {
        if (! self::$routesCleared) {
            Artisan::call('route:clear');
            self::$routesCleared = true;
        }
    }

    private function actingAsUser(): static
    {
        return $this->withHeader('X-Tenant-ID', '1');
    }

    private function makeDomain(): TenantDomain
    {
        return new TenantDomain(
            tenantId: 1,
            domain: 'test.example.com',
            isPrimary: true,
            isVerified: true,
            id: 10,
        );
    }

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    public function test_index_returns_paginated_domains(): void
    {
        $domain = $this->makeDomain();

        $this->findDomainsService
            ->expects($this->once())
            ->method('paginateByTenant')
            ->willReturn(new LengthAwarePaginator([$domain], 1, 15, 1));

        $response = $this->actingAsUser()
            ->getJson('/api/tenants/1/domains');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 10)
            ->assertJsonPath('data.0.domain', 'test.example.com');
    }

    // -------------------------------------------------------------------------
    // show
    // -------------------------------------------------------------------------

    public function test_show_returns_domain_resource(): void
    {
        $domain = $this->makeDomain();

        $this->findDomainsService
            ->method('find')
            ->willReturn($domain);

        $response = $this->actingAsUser()
            ->getJson('/api/tenants/1/domains/10');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 10)
            ->assertJsonPath('data.domain', 'test.example.com');
    }

    public function test_show_returns_404_when_not_found(): void
    {
        $this->findDomainsService
            ->method('find')
            ->willReturn(null);

        $response = $this->actingAsUser()
            ->getJson('/api/tenants/1/domains/999');

        $response->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    // -------------------------------------------------------------------------
    // store
    // -------------------------------------------------------------------------

    public function test_store_creates_domain(): void
    {
        $domain = $this->makeDomain();

        $this->createDomainService
            ->expects($this->once())
            ->method('execute')
            ->willReturn($domain);

        $response = $this->actingAsUser()
            ->postJson('/api/tenants/1/domains', [
                'domain' => 'test.example.com',
                'is_primary' => false,
                'is_verified' => false,
            ]);

        $response->assertStatus(HttpResponse::HTTP_CREATED)
            ->assertJsonPath('data.id', 10)
            ->assertJsonPath('data.domain', 'test.example.com');
    }

    // -------------------------------------------------------------------------
    // update
    // -------------------------------------------------------------------------

    public function test_update_modifies_domain(): void
    {
        $domain = $this->makeDomain();

        $this->findDomainsService
            ->method('find')
            ->willReturn($domain);

        $this->updateDomainService
            ->expects($this->once())
            ->method('execute')
            ->willReturn($domain);

        $response = $this->actingAsUser()
            ->patchJson('/api/tenants/1/domains/10', [
                'is_primary' => true,
            ]);

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 10);
    }

    // -------------------------------------------------------------------------
    // destroy
    // -------------------------------------------------------------------------

    public function test_destroy_deletes_domain(): void
    {
        $domain = $this->makeDomain();

        $this->findDomainsService
            ->method('find')
            ->willReturn($domain);

        $this->deleteDomainService
            ->expects($this->once())
            ->method('execute');

        $response = $this->actingAsUser()
            ->deleteJson('/api/tenants/1/domains/10');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('message', 'Tenant domain deleted successfully');
    }
}
