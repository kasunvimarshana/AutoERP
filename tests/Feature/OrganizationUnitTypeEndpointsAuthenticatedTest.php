<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\PresenceVerifierInterface;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\CreateOrganizationUnitTypeServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitTypeServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\FindOrganizationUnitTypeServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\UpdateOrganizationUnitTypeServiceInterface;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnitType;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class OrganizationUnitTypeEndpointsAuthenticatedTest extends TestCase
{
    private static bool $routesCleared = false;

    /** @var FindOrganizationUnitTypeServiceInterface&MockObject */
    private FindOrganizationUnitTypeServiceInterface $findService;

    /** @var CreateOrganizationUnitTypeServiceInterface&MockObject */
    private CreateOrganizationUnitTypeServiceInterface $createService;

    /** @var UpdateOrganizationUnitTypeServiceInterface&MockObject */
    private UpdateOrganizationUnitTypeServiceInterface $updateService;

    /** @var DeleteOrganizationUnitTypeServiceInterface&MockObject */
    private DeleteOrganizationUnitTypeServiceInterface $deleteService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clearRoutesCacheOnce();

        $this->findService = $this->createMock(FindOrganizationUnitTypeServiceInterface::class);
        $this->createService = $this->createMock(CreateOrganizationUnitTypeServiceInterface::class);
        $this->updateService = $this->createMock(UpdateOrganizationUnitTypeServiceInterface::class);
        $this->deleteService = $this->createMock(DeleteOrganizationUnitTypeServiceInterface::class);

        $this->app->instance(FindOrganizationUnitTypeServiceInterface::class, $this->findService);
        $this->app->instance(CreateOrganizationUnitTypeServiceInterface::class, $this->createService);
        $this->app->instance(UpdateOrganizationUnitTypeServiceInterface::class, $this->updateService);
        $this->app->instance(DeleteOrganizationUnitTypeServiceInterface::class, $this->deleteService);

        $authService = $this->createMock(AuthorizationServiceInterface::class);
        $authService->method('can')->willReturn(true);
        $this->app->instance(AuthorizationServiceInterface::class, $authService);

        $tenantConfigClient = $this->createMock(TenantConfigClientInterface::class);
        $tenantConfigClient->method('getConfig')->willReturn(null);
        $this->app->instance(TenantConfigClientInterface::class, $tenantConfigClient);
        $this->app->instance(TenantConfigManagerInterface::class, $this->createMock(TenantConfigManagerInterface::class));

        $presenceVerifier = $this->createMock(PresenceVerifierInterface::class);
        $presenceVerifier->method('getCount')->willReturnCallback(
            static function (string $collection, string $column): int {
                if ($collection === 'org_unit_types') {
                    return 0; // unique — name not taken
                }

                return 1; // exists
            }
        );
        $presenceVerifier->method('getMultiCount')->willReturn(1);
        $this->app->instance(PresenceVerifierInterface::class, $presenceVerifier);
        $this->app['validator']->setPresenceVerifier($presenceVerifier);

        $user = new UserModel([
            'email' => 'type.test@example.com',
            'password' => 'secret',
            'first_name' => 'Type',
            'last_name' => 'Tester',
        ]);
        $user->setAttribute('id', 99);
        $user->setAttribute('tenant_id', 1);

        $this->actingAs($user, (string) config('auth_context.guards.api', config('auth.defaults.guard', 'api')));
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

    private function buildType(int $id, string $name = 'Department'): OrganizationUnitType
    {
        return new OrganizationUnitType(
            tenantId: 1,
            name: $name,
            level: 1,
            isActive: true,
            id: $id,
        );
    }

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    public function test_index_returns_paginated_list(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [$this->buildType(id: 5)],
            total: 1,
            perPage: 15,
            currentPage: 1,
        );

        $this->findService
            ->expects($this->once())
            ->method('list')
            ->willReturn($paginator);

        $response = $this->actingAsUser()
            ->getJson('/api/organization-unit-types?tenant_id=1');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 5)
            ->assertJsonPath('data.0.name', 'Department');
    }

    // -------------------------------------------------------------------------
    // show
    // -------------------------------------------------------------------------

    public function test_show_returns_single_resource(): void
    {
        $this->findService
            ->expects($this->once())
            ->method('find')
            ->with(5)
            ->willReturn($this->buildType(id: 5));

        $response = $this->actingAsUser()
            ->getJson('/api/organization-unit-types/5');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 5)
            ->assertJsonPath('data.name', 'Department');
    }

    public function test_show_returns_404_when_not_found(): void
    {
        $this->findService
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $response = $this->actingAsUser()
            ->getJson('/api/organization-unit-types/999');

        $response->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    // -------------------------------------------------------------------------
    // store
    // -------------------------------------------------------------------------

    public function test_store_returns_created_resource(): void
    {
        $this->createService
            ->expects($this->once())
            ->method('execute')
            ->willReturn($this->buildType(id: 7));

        $response = $this->actingAsUser()
            ->postJson('/api/organization-unit-types', [
                'tenant_id' => 1,
                'name' => 'Division',
                'level' => 1,
                'is_active' => true,
            ]);

        $response->assertStatus(HttpResponse::HTTP_CREATED)
            ->assertJsonPath('data.id', 7)
            ->assertJsonPath('data.name', 'Department');
    }

    // -------------------------------------------------------------------------
    // update
    // -------------------------------------------------------------------------

    public function test_update_returns_updated_resource(): void
    {
        $this->findService
            ->method('find')
            ->with(5)
            ->willReturn($this->buildType(id: 5));

        $this->updateService
            ->expects($this->once())
            ->method('execute')
            ->willReturn($this->buildType(id: 5, name: 'Updated Type'));

        $response = $this->actingAsUser()
            ->putJson('/api/organization-unit-types/5', [
                'tenant_id' => 1,
                'name' => 'Updated Type',
                'level' => 2,
                'is_active' => true,
            ]);

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 5)
            ->assertJsonPath('data.name', 'Updated Type');
    }

    // -------------------------------------------------------------------------
    // destroy
    // -------------------------------------------------------------------------

    public function test_destroy_returns_success_message(): void
    {
        $this->findService
            ->method('find')
            ->with(5)
            ->willReturn($this->buildType(id: 5));

        $this->deleteService
            ->expects($this->once())
            ->method('execute');

        $response = $this->actingAsUser()
            ->deleteJson('/api/organization-unit-types/5');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('message', 'Organization unit type deleted successfully');
    }
}
