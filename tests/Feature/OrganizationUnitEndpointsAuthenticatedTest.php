<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\PresenceVerifierInterface;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\CreateOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\CreateOrganizationUnitTypeServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\CreateOrganizationUnitUserServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitAttachmentServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitTypeServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitUserServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\FindOrganizationUnitAttachmentsServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\FindOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\FindOrganizationUnitTypeServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\FindOrganizationUnitUserServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\UpdateOrganizationUnitServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\UpdateOrganizationUnitTypeServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\UpdateOrganizationUnitUserServiceInterface;
use Modules\OrganizationUnit\Application\Contracts\UploadOrganizationUnitAttachmentServiceInterface;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnitAttachment;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnitType;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnitUser;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Application\Contracts\FindUserServiceInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class OrganizationUnitEndpointsAuthenticatedTest extends TestCase
{
    private static bool $routesCleared = false;

    /** @var FindOrganizationUnitServiceInterface&MockObject */
    private FindOrganizationUnitServiceInterface $findOrgUnitService;

    /** @var CreateOrganizationUnitServiceInterface&MockObject */
    private CreateOrganizationUnitServiceInterface $createOrgUnitService;

    /** @var UpdateOrganizationUnitServiceInterface&MockObject */
    private UpdateOrganizationUnitServiceInterface $updateOrgUnitService;

    /** @var FindOrganizationUnitTypeServiceInterface&MockObject */
    private FindOrganizationUnitTypeServiceInterface $findOrgUnitTypeService;

    /** @var CreateOrganizationUnitTypeServiceInterface&MockObject */
    private CreateOrganizationUnitTypeServiceInterface $createOrgUnitTypeService;

    /** @var FindOrganizationUnitUserServiceInterface&MockObject */
    private FindOrganizationUnitUserServiceInterface $findOrgUnitUserService;

    /** @var CreateOrganizationUnitUserServiceInterface&MockObject */
    private CreateOrganizationUnitUserServiceInterface $createOrgUnitUserService;

    /** @var FindOrganizationUnitAttachmentsServiceInterface&MockObject */
    private FindOrganizationUnitAttachmentsServiceInterface $findAttachmentsService;

    /** @var UploadOrganizationUnitAttachmentServiceInterface&MockObject */
    private UploadOrganizationUnitAttachmentServiceInterface $uploadAttachmentService;

    /** @var DeleteOrganizationUnitAttachmentServiceInterface&MockObject */
    private DeleteOrganizationUnitAttachmentServiceInterface $deleteAttachmentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clearRoutesCacheOnce();

        // — OrgUnit services —
        $this->findOrgUnitService = $this->createMock(FindOrganizationUnitServiceInterface::class);
        $this->createOrgUnitService = $this->createMock(CreateOrganizationUnitServiceInterface::class);
        $this->updateOrgUnitService = $this->createMock(UpdateOrganizationUnitServiceInterface::class);

        $this->app->instance(FindOrganizationUnitServiceInterface::class, $this->findOrgUnitService);
        $this->app->instance(CreateOrganizationUnitServiceInterface::class, $this->createOrgUnitService);
        $this->app->instance(UpdateOrganizationUnitServiceInterface::class, $this->updateOrgUnitService);
        $this->app->instance(DeleteOrganizationUnitServiceInterface::class, $this->createMock(DeleteOrganizationUnitServiceInterface::class));

        // — OrgUnitType services —
        $this->findOrgUnitTypeService = $this->createMock(FindOrganizationUnitTypeServiceInterface::class);
        $this->createOrgUnitTypeService = $this->createMock(CreateOrganizationUnitTypeServiceInterface::class);

        $this->app->instance(FindOrganizationUnitTypeServiceInterface::class, $this->findOrgUnitTypeService);
        $this->app->instance(CreateOrganizationUnitTypeServiceInterface::class, $this->createOrgUnitTypeService);
        $this->app->instance(UpdateOrganizationUnitTypeServiceInterface::class, $this->createMock(UpdateOrganizationUnitTypeServiceInterface::class));
        $this->app->instance(DeleteOrganizationUnitTypeServiceInterface::class, $this->createMock(DeleteOrganizationUnitTypeServiceInterface::class));

        // — OrgUnitUser services —
        $this->findOrgUnitUserService = $this->createMock(FindOrganizationUnitUserServiceInterface::class);
        $this->createOrgUnitUserService = $this->createMock(CreateOrganizationUnitUserServiceInterface::class);

        $this->app->instance(FindOrganizationUnitUserServiceInterface::class, $this->findOrgUnitUserService);
        $this->app->instance(CreateOrganizationUnitUserServiceInterface::class, $this->createOrgUnitUserService);
        $this->app->instance(UpdateOrganizationUnitUserServiceInterface::class, $this->createMock(UpdateOrganizationUnitUserServiceInterface::class));
        $this->app->instance(DeleteOrganizationUnitUserServiceInterface::class, $this->createMock(DeleteOrganizationUnitUserServiceInterface::class));

        // — Attachment services —
        $this->findAttachmentsService = $this->createMock(FindOrganizationUnitAttachmentsServiceInterface::class);
        $this->uploadAttachmentService = $this->createMock(UploadOrganizationUnitAttachmentServiceInterface::class);
        $this->deleteAttachmentService = $this->createMock(DeleteOrganizationUnitAttachmentServiceInterface::class);

        $this->app->instance(FindOrganizationUnitAttachmentsServiceInterface::class, $this->findAttachmentsService);
        $this->app->instance(UploadOrganizationUnitAttachmentServiceInterface::class, $this->uploadAttachmentService);
        $this->app->instance(DeleteOrganizationUnitAttachmentServiceInterface::class, $this->deleteAttachmentService);

        // — Support services —
        $this->app->instance(FindUserServiceInterface::class, $this->createMock(FindUserServiceInterface::class));

        $storage = $this->createMock(FileStorageServiceInterface::class);
        $storage->method('url')->willReturn('https://cdn.example.com/file.jpg');
        $this->app->instance(FileStorageServiceInterface::class, $storage);

        // — Auth/Tenant —
        $authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $authorizationService->method('can')->willReturn(true);
        $this->app->instance(AuthorizationServiceInterface::class, $authorizationService);

        $tenantConfigClient = $this->createMock(TenantConfigClientInterface::class);
        $tenantConfigClient->method('getConfig')->willReturn(null);
        $this->app->instance(TenantConfigClientInterface::class, $tenantConfigClient);

        $this->app->instance(TenantConfigManagerInterface::class, $this->createMock(TenantConfigManagerInterface::class));

        $presenceVerifier = $this->createMock(PresenceVerifierInterface::class);
        $presenceVerifier->method('getCount')->willReturnCallback(
            static function (string $collection, string $column): int {
                // Allow uniqueness to pass for org_unit_users.user_id and org_unit_types.name
                if (in_array($collection, ['org_unit_users', 'org_unit_types'], true)) {
                    return 0;
                }

                return 1;
            }
        );
        $presenceVerifier->method('getMultiCount')->willReturn(1);
        $this->app->instance(PresenceVerifierInterface::class, $presenceVerifier);
        $this->app['validator']->setPresenceVerifier($presenceVerifier);

        $user = new UserModel([
            'id' => 1,
            'tenant_id' => 1,
            'email' => 'ou.test@example.com',
            'password' => 'secret',
            'first_name' => 'OrgUnit',
            'last_name' => 'Tester',
        ]);
        $user->setAttribute('id', 1);
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

    // -------------------------------------------------------------------------
    // Organization Unit – index / show
    // -------------------------------------------------------------------------

    public function test_org_unit_index_returns_paginated_list(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [$this->buildOrgUnit(id: 10)],
            total: 1,
            perPage: 15,
            currentPage: 1,
        );

        $this->findOrgUnitService
            ->expects($this->once())
            ->method('list')
            ->willReturn($paginator);

        $response = $this->withHeader('X-Tenant-ID', '1')
            ->getJson('/api/organization-units?tenant_id=1');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 10)
            ->assertJsonPath('data.0.name', 'Engineering')
            ->assertJsonPath('data.0.tenant_id', 1);
    }

    public function test_org_unit_show_returns_single_resource(): void
    {
        $this->findOrgUnitService
            ->expects($this->once())
            ->method('find')
            ->with(10)
            ->willReturn($this->buildOrgUnit(id: 10));

        $response = $this->withHeader('X-Tenant-ID', '1')
            ->getJson('/api/organization-units/10');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 10)
            ->assertJsonPath('data.name', 'Engineering');
    }

    public function test_org_unit_show_returns_404_when_not_found(): void
    {
        $this->findOrgUnitService
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $response = $this->withHeader('X-Tenant-ID', '1')
            ->getJson('/api/organization-units/999');

        $response->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    public function test_org_unit_store_returns_created_resource(): void
    {
        $this->createOrgUnitService
            ->expects($this->once())
            ->method('execute')
            ->willReturn($this->buildOrgUnit(id: 20));

        $response = $this->withHeader('X-Tenant-ID', '1')
            ->postJson('/api/organization-units', [
                'tenant_id' => 1,
                'name' => 'Engineering',
            ]);

        $response->assertStatus(HttpResponse::HTTP_CREATED)
            ->assertJsonPath('data.id', 20)
            ->assertJsonPath('data.name', 'Engineering');
    }

    public function test_org_unit_store_returns_422_when_name_missing(): void
    {
        $this->createOrgUnitService
            ->expects($this->never())
            ->method('execute');

        $response = $this->withHeader('X-Tenant-ID', '1')
            ->postJson('/api/organization-units', [
                'tenant_id' => 1,
            ]);

        $response->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_org_unit_update_returns_ok(): void
    {
        $this->findOrgUnitService
            ->expects($this->once())
            ->method('find')
            ->with(10)
            ->willReturn($this->buildOrgUnit(id: 10));

        $this->updateOrgUnitService
            ->expects($this->once())
            ->method('execute')
            ->willReturn($this->buildOrgUnit(id: 10, name: 'Updated Dept'));

        $response = $this->withHeader('X-Tenant-ID', '1')
            ->putJson('/api/organization-units/10', [
                'tenant_id' => 1,
                'name' => 'Updated Dept',
            ]);

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 10)
            ->assertJsonPath('data.name', 'Updated Dept');
    }

    // -------------------------------------------------------------------------
    // Organization Unit Type – index / show / store
    // -------------------------------------------------------------------------

    public function test_org_unit_type_index_returns_paginated_list(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [$this->buildOrgUnitType(id: 5)],
            total: 1,
            perPage: 15,
            currentPage: 1,
        );

        $this->findOrgUnitTypeService
            ->expects($this->once())
            ->method('list')
            ->willReturn($paginator);

        $response = $this->withHeader('X-Tenant-ID', '1')
            ->getJson('/api/organization-unit-types?tenant_id=1');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 5)
            ->assertJsonPath('data.0.name', 'Department');
    }

    public function test_org_unit_type_show_returns_single_resource(): void
    {
        $this->findOrgUnitTypeService
            ->expects($this->once())
            ->method('find')
            ->with(5)
            ->willReturn($this->buildOrgUnitType(id: 5));

        $response = $this->withHeader('X-Tenant-ID', '1')
            ->getJson('/api/organization-unit-types/5');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 5)
            ->assertJsonPath('data.name', 'Department');
    }

    public function test_org_unit_type_show_returns_404_when_not_found(): void
    {
        $this->findOrgUnitTypeService
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $response = $this->withHeader('X-Tenant-ID', '1')
            ->getJson('/api/organization-unit-types/999');

        $response->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    public function test_org_unit_type_store_returns_created_resource(): void
    {
        $this->createOrgUnitTypeService
            ->expects($this->once())
            ->method('execute')
            ->willReturn($this->buildOrgUnitType(id: 7));

        $response = $this->withHeader('X-Tenant-ID', '1')
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
    // Organization Unit Users – nested resource
    // -------------------------------------------------------------------------

    public function test_org_unit_user_index_returns_paginated_list(): void
    {
        $this->findOrgUnitService
            ->expects($this->once())
            ->method('find')
            ->with(10)
            ->willReturn($this->buildOrgUnit(id: 10));

        $paginator = new LengthAwarePaginator(
            items: [$this->buildOrgUnitUser(id: 100, orgUnitId: 10)],
            total: 1,
            perPage: 15,
            currentPage: 1,
        );

        $this->findOrgUnitUserService
            ->expects($this->once())
            ->method('list')
            ->willReturn($paginator);

        $response = $this->withHeader('X-Tenant-ID', '1')
            ->getJson('/api/organization-units/10/users?tenant_id=1');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 100)
            ->assertJsonPath('data.0.org_unit_id', 10)
            ->assertJsonPath('data.0.user_id', 42);
    }

    public function test_org_unit_user_show_returns_single_resource(): void
    {
        $this->findOrgUnitService
            ->expects($this->once())
            ->method('find')
            ->with(10)
            ->willReturn($this->buildOrgUnit(id: 10));

        $this->findOrgUnitUserService
            ->expects($this->once())
            ->method('find')
            ->with(100)
            ->willReturn($this->buildOrgUnitUser(id: 100, orgUnitId: 10));

        $response = $this->withHeader('X-Tenant-ID', '1')
            ->getJson('/api/organization-units/10/users/100');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 100)
            ->assertJsonPath('data.user_id', 42);
    }

    public function test_org_unit_user_store_returns_created_resource(): void
    {
        $this->findOrgUnitService
            ->expects($this->once())
            ->method('find')
            ->with(10)
            ->willReturn($this->buildOrgUnit(id: 10));

        $this->createOrgUnitUserService
            ->expects($this->once())
            ->method('execute')
            ->willReturn($this->buildOrgUnitUser(id: 101, orgUnitId: 10));

        $response = $this->withHeader('X-Tenant-ID', '1')
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

    public function test_org_unit_user_show_returns_404_for_mismatched_unit(): void
    {
        $this->findOrgUnitService
            ->expects($this->once())
            ->method('find')
            ->with(10)
            ->willReturn($this->buildOrgUnit(id: 10));

        // User belongs to org unit 99, not 10
        $this->findOrgUnitUserService
            ->expects($this->once())
            ->method('find')
            ->with(100)
            ->willReturn($this->buildOrgUnitUser(id: 100, orgUnitId: 99));

        $response = $this->withHeader('X-Tenant-ID', '1')
            ->getJson('/api/organization-units/10/users/100');

        $response->assertStatus(HttpResponse::HTTP_NOT_FOUND);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function buildOrgUnit(int $id, string $name = 'Engineering'): OrganizationUnit
    {
        return new OrganizationUnit(
            tenantId: 1,
            name: $name,
            id: $id,
        );
    }

    private function buildOrgUnitType(int $id, string $name = 'Department'): OrganizationUnitType
    {
        return new OrganizationUnitType(
            tenantId: 1,
            name: $name,
            id: $id,
        );
    }

    private function buildOrgUnitUser(int $id, int $orgUnitId, int $userId = 42): OrganizationUnitUser
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

    private function buildAttachment(int $id, int $orgUnitId): OrganizationUnitAttachment
    {
        return new OrganizationUnitAttachment(
            tenantId: 1,
            organizationUnitId: $orgUnitId,
            uuid: 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee',
            name: 'document.pdf',
            filePath: 'org-unit-attachments/document.pdf',
            mimeType: 'application/pdf',
            size: 1024,
            type: 'contract',
            metadata: null,
            id: $id,
            createdAt: new \DateTime('2024-01-01'),
            updatedAt: new \DateTime('2024-01-01'),
        );
    }
}
