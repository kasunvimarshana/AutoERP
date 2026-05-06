<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\PresenceVerifierInterface;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\CreateOrganizationUnitUserServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitUserServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\FindOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\FindOrganizationUnitUserServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\UpdateOrganizationUnitUserServiceInterface;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnitUser;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Application\Contracts\FindUserServiceInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class OrganizationUnitUserEndpointsAuthenticatedTest extends TestCase
{
    private static bool $routesCleared = false;

    /** @var FindOrganizationUnitServiceInterface&MockObject */
    private FindOrganizationUnitServiceInterface $findOrgUnitService;

    /** @var FindOrganizationUnitUserServiceInterface&MockObject */
    private FindOrganizationUnitUserServiceInterface $findService;

    /** @var CreateOrganizationUnitUserServiceInterface&MockObject */
    private CreateOrganizationUnitUserServiceInterface $createService;

    /** @var UpdateOrganizationUnitUserServiceInterface&MockObject */
    private UpdateOrganizationUnitUserServiceInterface $updateService;

    /** @var DeleteOrganizationUnitUserServiceInterface&MockObject */
    private DeleteOrganizationUnitUserServiceInterface $deleteService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clearRoutesCacheOnce();

        $this->findOrgUnitService = $this->createMock(FindOrganizationUnitServiceInterface::class);
        $this->findService = $this->createMock(FindOrganizationUnitUserServiceInterface::class);
        $this->createService = $this->createMock(CreateOrganizationUnitUserServiceInterface::class);
        $this->updateService = $this->createMock(UpdateOrganizationUnitUserServiceInterface::class);
        $this->deleteService = $this->createMock(DeleteOrganizationUnitUserServiceInterface::class);

        $this->app->instance(FindOrganizationUnitServiceInterface::class, $this->findOrgUnitService);
        $this->app->instance(FindOrganizationUnitUserServiceInterface::class, $this->findService);
        $this->app->instance(CreateOrganizationUnitUserServiceInterface::class, $this->createService);
        $this->app->instance(UpdateOrganizationUnitUserServiceInterface::class, $this->updateService);
        $this->app->instance(DeleteOrganizationUnitUserServiceInterface::class, $this->deleteService);

        // Other OrgUnit services referenced via the IoC container (routes share same provider)
        $this->app->instance(\Modules\OrganizationUnit\Application\Contracts\CreateOrganizationUnitServiceInterface::class, $this->createMock(\Modules\OrganizationUnit\Application\Contracts\CreateOrganizationUnitServiceInterface::class));
        $this->app->instance(\Modules\OrganizationUnit\Application\Contracts\UpdateOrganizationUnitServiceInterface::class, $this->createMock(\Modules\OrganizationUnit\Application\Contracts\UpdateOrganizationUnitServiceInterface::class));
        $this->app->instance(\Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitServiceInterface::class, $this->createMock(\Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitServiceInterface::class));
        $this->app->instance(\Modules\OrganizationUnit\Application\Contracts\FindOrganizationUnitTypeServiceInterface::class, $this->createMock(\Modules\OrganizationUnit\Application\Contracts\FindOrganizationUnitTypeServiceInterface::class));
        $this->app->instance(\Modules\OrganizationUnit\Application\Contracts\CreateOrganizationUnitTypeServiceInterface::class, $this->createMock(\Modules\OrganizationUnit\Application\Contracts\CreateOrganizationUnitTypeServiceInterface::class));
        $this->app->instance(\Modules\OrganizationUnit\Application\Contracts\UpdateOrganizationUnitTypeServiceInterface::class, $this->createMock(\Modules\OrganizationUnit\Application\Contracts\UpdateOrganizationUnitTypeServiceInterface::class));
        $this->app->instance(\Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitTypeServiceInterface::class, $this->createMock(\Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitTypeServiceInterface::class));
        $this->app->instance(\Modules\OrganizationUnit\Application\Contracts\FindOrganizationUnitAttachmentsServiceInterface::class, $this->createMock(\Modules\OrganizationUnit\Application\Contracts\FindOrganizationUnitAttachmentsServiceInterface::class));
        $this->app->instance(\Modules\OrganizationUnit\Application\Contracts\UploadOrganizationUnitAttachmentServiceInterface::class, $this->createMock(\Modules\OrganizationUnit\Application\Contracts\UploadOrganizationUnitAttachmentServiceInterface::class));
        $this->app->instance(\Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitAttachmentServiceInterface::class, $this->createMock(\Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitAttachmentServiceInterface::class));

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
                if ($collection === 'org_unit_users') {
                    return 0; // unique — user not already in unit
                }

                return 1; // exists
            }
        );
        $presenceVerifier->method('getMultiCount')->willReturn(1);
        $this->app->instance(PresenceVerifierInterface::class, $presenceVerifier);
        $this->app['validator']->setPresenceVerifier($presenceVerifier);

        $user = new UserModel([
            'email' => 'ouuser.test@example.com',
            'password' => 'secret',
            'first_name' => 'OUUser',
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

    private function buildOrgUnit(int $id): OrganizationUnit
    {
        return new OrganizationUnit(
            tenantId: 1,
            name: 'Engineering',
            id: $id,
        );
    }

    private function buildUser(int $id, int $orgUnitId, int $userId = 42): OrganizationUnitUser
    {
        return new OrganizationUnitUser(
            tenantId: 1,
            organizationUnitId: $orgUnitId,
            userId: $userId,
            roleId: 5,
            isPrimary: false,
            id: $id,
        );
    }

    // -------------------------------------------------------------------------
    // index
    // -------------------------------------------------------------------------

    public function test_index_returns_paginated_users(): void
    {
        $this->findOrgUnitService
            ->expects($this->once())
            ->method('find')
            ->with(10)
            ->willReturn($this->buildOrgUnit(id: 10));

        $paginator = new LengthAwarePaginator(
            items: [$this->buildUser(id: 100, orgUnitId: 10)],
            total: 1,
            perPage: 15,
            currentPage: 1,
        );

        $this->findService
            ->expects($this->once())
            ->method('list')
            ->willReturn($paginator);

        $response = $this->actingAsUser()
            ->getJson('/api/organization-units/10/users?tenant_id=1');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 100)
            ->assertJsonPath('data.0.org_unit_id', 10)
            ->assertJsonPath('data.0.user_id', 42);
    }

    // -------------------------------------------------------------------------
    // show
    // -------------------------------------------------------------------------

    public function test_show_returns_single_user(): void
    {
        $this->findOrgUnitService
            ->expects($this->once())
            ->method('find')
            ->with(10)
            ->willReturn($this->buildOrgUnit(id: 10));

        $this->findService
            ->expects($this->once())
            ->method('find')
            ->with(100)
            ->willReturn($this->buildUser(id: 100, orgUnitId: 10));

        $response = $this->actingAsUser()
            ->getJson('/api/organization-units/10/users/100');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 100)
            ->assertJsonPath('data.user_id', 42);
    }

    public function test_show_returns_404_for_mismatched_org_unit(): void
    {
        $this->findOrgUnitService
            ->expects($this->once())
            ->method('find')
            ->with(10)
            ->willReturn($this->buildOrgUnit(id: 10));

        $this->findService
            ->expects($this->once())
            ->method('find')
            ->with(100)
            ->willReturn($this->buildUser(id: 100, orgUnitId: 99)); // belongs to unit 99

        $response = $this->actingAsUser()
            ->getJson('/api/organization-units/10/users/100');

        $response->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    // -------------------------------------------------------------------------
    // store
    // -------------------------------------------------------------------------

    public function test_store_returns_created_user(): void
    {
        $this->findOrgUnitService
            ->expects($this->once())
            ->method('find')
            ->with(10)
            ->willReturn($this->buildOrgUnit(id: 10));

        $this->createService
            ->expects($this->once())
            ->method('execute')
            ->willReturn($this->buildUser(id: 101, orgUnitId: 10));

        $response = $this->actingAsUser()
            ->postJson('/api/organization-units/10/users', [
                'tenant_id' => 1,
                'org_unit_id' => 10,
                'user_id' => 42,
                'role_id' => 5,
                'is_primary' => false,
            ]);

        $response->assertStatus(HttpResponse::HTTP_CREATED)
            ->assertJsonPath('data.id', 101)
            ->assertJsonPath('data.user_id', 42);
    }

    // -------------------------------------------------------------------------
    // update
    // -------------------------------------------------------------------------

    public function test_update_returns_updated_user(): void
    {
        $this->findOrgUnitService
            ->expects($this->once())
            ->method('find')
            ->with(10)
            ->willReturn($this->buildOrgUnit(id: 10));

        $this->findService
            ->expects($this->once())
            ->method('find')
            ->with(100)
            ->willReturn($this->buildUser(id: 100, orgUnitId: 10));

        $this->updateService
            ->expects($this->once())
            ->method('execute')
            ->willReturn($this->buildUser(id: 100, orgUnitId: 10));

        $response = $this->actingAsUser()
            ->putJson('/api/organization-units/10/users/100', [
                'tenant_id' => 1,
                'org_unit_id' => 10,
                'user_id' => 42,
                'is_primary' => true,
            ]);

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 100)
            ->assertJsonPath('data.user_id', 42);
    }

    // -------------------------------------------------------------------------
    // destroy
    // -------------------------------------------------------------------------

    public function test_destroy_returns_success_message(): void
    {
        $this->findOrgUnitService
            ->expects($this->once())
            ->method('find')
            ->with(10)
            ->willReturn($this->buildOrgUnit(id: 10));

        $this->findService
            ->expects($this->once())
            ->method('find')
            ->with(100)
            ->willReturn($this->buildUser(id: 100, orgUnitId: 10));

        $this->deleteService
            ->expects($this->once())
            ->method('execute');

        $response = $this->actingAsUser()
            ->deleteJson('/api/organization-units/10/users/100');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('message', 'Organization unit user deleted successfully');
    }
}
