<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\PresenceVerifierInterface;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Core\Application\Contracts\FileStorageServiceInterface;
use Modules\Tenant\Application\Contracts\BulkUploadTenantAttachmentsServiceInterface;
use Modules\Tenant\Application\Contracts\CreateTenantDomainServiceInterface;
use Modules\Tenant\Application\Contracts\CreateTenantPlanServiceInterface;
use Modules\Tenant\Application\Contracts\CreateTenantServiceInterface;
use Modules\Tenant\Application\Contracts\CreateTenantSettingServiceInterface;
use Modules\Tenant\Application\Contracts\DeleteTenantAttachmentServiceInterface;
use Modules\Tenant\Application\Contracts\DeleteTenantDomainServiceInterface;
use Modules\Tenant\Application\Contracts\DeleteTenantPlanServiceInterface;
use Modules\Tenant\Application\Contracts\DeleteTenantServiceInterface;
use Modules\Tenant\Application\Contracts\DeleteTenantSettingServiceInterface;
use Modules\Tenant\Application\Contracts\FindTenantAttachmentsServiceInterface;
use Modules\Tenant\Application\Contracts\FindTenantDomainsServiceInterface;
use Modules\Tenant\Application\Contracts\FindTenantPlansServiceInterface;
use Modules\Tenant\Application\Contracts\FindTenantServiceInterface;
use Modules\Tenant\Application\Contracts\FindTenantSettingsServiceInterface;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\Tenant\Application\Contracts\UpdateTenantConfigServiceInterface;
use Modules\Tenant\Application\Contracts\UpdateTenantDomainServiceInterface;
use Modules\Tenant\Application\Contracts\UpdateTenantPlanServiceInterface;
use Modules\Tenant\Application\Contracts\UpdateTenantServiceInterface;
use Modules\Tenant\Application\Contracts\UpdateTenantSettingServiceInterface;
use Modules\Tenant\Application\Contracts\UploadTenantAttachmentServiceInterface;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Domain\Entities\TenantAttachment;
use Modules\Tenant\Domain\Entities\TenantDomain;
use Modules\Tenant\Domain\Entities\TenantPlan;
use Modules\Tenant\Domain\Entities\TenantSetting;
use Modules\Tenant\Domain\ValueObjects\DatabaseConfig;
use Modules\User\Application\Contracts\FindUserServiceInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Tests\TestCase;

class TenantEndpointsAuthenticatedTest extends TestCase
{
    private UserModel $authUser;

    private AuthorizationServiceInterface $authorizationService;

    private FindTenantServiceInterface $findTenantService;

    private Tenant $tenantEntity;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authUser = new UserModel([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'hashed',
        ]);
        $this->authUser->setAttribute('id', 99);
        $this->authUser->setAttribute('tenant_id', 1);

        // Authorization mock — allow all
        $this->authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $this->authorizationService->method('can')->willReturn(true);
        $this->app->instance(AuthorizationServiceInterface::class, $this->authorizationService);

        // PresenceVerifier mock — 0 for unique fields, 1 for exists/id checks
        $presenceVerifier = $this->createMock(PresenceVerifierInterface::class);
        $presenceVerifier->method('getCount')->willReturnCallback(
            static function (string $collection, string $column): int {
                if (in_array($column, ['slug', 'domain', 'key'], true)) {
                    return 0; // unique — value not already taken
                }

                return 1; // exists — record found
            }
        );
        $presenceVerifier->method('getMultiCount')->willReturn(1);
        $this->app->instance(PresenceVerifierInterface::class, $presenceVerifier);
        $this->app['validator']->setPresenceVerifier($presenceVerifier);

        // FileStorageService mock
        $fileStorage = $this->createMock(FileStorageServiceInterface::class);
        $fileStorage->method('url')->willReturn('https://cdn.example.com/test-file');
        $this->app->instance(FileStorageServiceInterface::class, $fileStorage);

        // Build a reusable Tenant domain entity
        $this->tenantEntity = new Tenant(
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

        // FindTenantService — used by many controllers
        $this->findTenantService = $this->createMock(FindTenantServiceInterface::class);
        $this->findTenantService->method('find')->willReturn($this->tenantEntity);
        $this->app->instance(FindTenantServiceInterface::class, $this->findTenantService);

        // FindUserService — used by TenantController
        $findUserService = $this->createMock(FindUserServiceInterface::class);
        $findUserService->method('list')->willReturn(
            new LengthAwarePaginator([], 0, 15, 1)
        );
        $this->app->instance(FindUserServiceInterface::class, $findUserService);

        // TenantConfigClient and TenantConfigManager — needed by infrastructure
        $configClient = $this->createMock(TenantConfigClientInterface::class);
        $this->app->instance(TenantConfigClientInterface::class, $configClient);

        $configManager = $this->createMock(TenantConfigManagerInterface::class);
        $this->app->instance(TenantConfigManagerInterface::class, $configManager);

        Http::fake(['api.pwnedpasswords.com/*' => Http::response('', 404)]);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────────

    private function actingAsUser(): static
    {
        return $this->withHeader('X-Tenant-ID', '1')->actingAs(
            $this->authUser,
            (string) config('auth_context.guards.api', config('auth.defaults.guard', 'api'))
        );
    }

    private function makeTenantPaginator(Tenant $tenant): LengthAwarePaginator
    {
        return new LengthAwarePaginator([$tenant], 1, 15, 1);
    }

    // ─── TenantController ───────────────────────────────────────────────────────

    public function test_index_returns_paginated_tenants(): void
    {
        $findService = $this->createMock(FindTenantServiceInterface::class);
        $findService->method('list')->willReturn($this->makeTenantPaginator($this->tenantEntity));
        $findService->method('find')->willReturn($this->tenantEntity);
        $this->app->instance(FindTenantServiceInterface::class, $findService);

        $findAttachmentService = $this->createMock(FindTenantAttachmentsServiceInterface::class);
        $findAttachmentService->method('paginateByTenant')->willReturn(new LengthAwarePaginator([], 0, 15, 1));
        $this->app->instance(FindTenantAttachmentsServiceInterface::class, $findAttachmentService);

        $createService = $this->createMock(CreateTenantServiceInterface::class);
        $this->app->instance(CreateTenantServiceInterface::class, $createService);

        $uploadService = $this->createMock(UploadTenantAttachmentServiceInterface::class);
        $this->app->instance(UploadTenantAttachmentServiceInterface::class, $uploadService);

        $response = $this->actingAsUser()->getJson('/api/tenants');

        $response->assertOk()->assertJsonPath('data.0.id', 1);
    }

    public function test_show_returns_tenant_resource(): void
    {
        $findAttachmentService = $this->createMock(FindTenantAttachmentsServiceInterface::class);
        $findAttachmentService->method('paginateByTenant')->willReturn(new LengthAwarePaginator([], 0, 15, 1));
        $this->app->instance(FindTenantAttachmentsServiceInterface::class, $findAttachmentService);

        $createService = $this->createMock(CreateTenantServiceInterface::class);
        $this->app->instance(CreateTenantServiceInterface::class, $createService);

        $uploadService = $this->createMock(UploadTenantAttachmentServiceInterface::class);
        $this->app->instance(UploadTenantAttachmentServiceInterface::class, $uploadService);

        $response = $this->actingAsUser()->getJson('/api/tenants/1');

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_store_creates_tenant(): void
    {
        $createService = $this->createMock(CreateTenantServiceInterface::class);
        $createService->method('execute')->willReturn($this->tenantEntity);
        $this->app->instance(CreateTenantServiceInterface::class, $createService);

        $findAttachmentService = $this->createMock(FindTenantAttachmentsServiceInterface::class);
        $this->app->instance(FindTenantAttachmentsServiceInterface::class, $findAttachmentService);

        $uploadService = $this->createMock(UploadTenantAttachmentServiceInterface::class);
        $this->app->instance(UploadTenantAttachmentServiceInterface::class, $uploadService);

        $payload = [
            'name' => 'Test Corp',
            'database_config' => [
                'driver' => 'mysql',
                'host' => 'localhost',
                'port' => 3306,
                'database' => 'testdb',
                'username' => 'root',
                'password' => 'secret',
            ],
            'plan' => 'free',
            'status' => 'active',
            'active' => true,
        ];

        $response = $this->actingAsUser()->postJson('/api/tenants', $payload);

        $response->assertCreated()->assertJsonPath('data.id', 1);
    }

    public function test_update_modifies_tenant(): void
    {
        $updateService = $this->createMock(UpdateTenantServiceInterface::class);
        $updateService->method('execute')->willReturn($this->tenantEntity);
        $this->app->instance(UpdateTenantServiceInterface::class, $updateService);

        $findAttachmentService = $this->createMock(FindTenantAttachmentsServiceInterface::class);
        $this->app->instance(FindTenantAttachmentsServiceInterface::class, $findAttachmentService);

        $createService = $this->createMock(CreateTenantServiceInterface::class);
        $this->app->instance(CreateTenantServiceInterface::class, $createService);

        $uploadService = $this->createMock(UploadTenantAttachmentServiceInterface::class);
        $this->app->instance(UploadTenantAttachmentServiceInterface::class, $uploadService);

        $response = $this->actingAsUser()->putJson('/api/tenants/1', ['name' => 'Updated Corp']);

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_update_config_modifies_tenant_config(): void
    {
        $updatedTenant = $this->tenantEntity;

        $updateConfigService = $this->createMock(UpdateTenantConfigServiceInterface::class);
        $updateConfigService->method('execute')->willReturn($updatedTenant);
        $this->app->instance(UpdateTenantConfigServiceInterface::class, $updateConfigService);

        $findAttachmentService = $this->createMock(FindTenantAttachmentsServiceInterface::class);
        $this->app->instance(FindTenantAttachmentsServiceInterface::class, $findAttachmentService);

        $createService = $this->createMock(CreateTenantServiceInterface::class);
        $this->app->instance(CreateTenantServiceInterface::class, $createService);

        $uploadService = $this->createMock(UploadTenantAttachmentServiceInterface::class);
        $this->app->instance(UploadTenantAttachmentServiceInterface::class, $uploadService);

        $response = $this->actingAsUser()->patchJson('/api/tenants/1/config', [
            'feature_flags' => ['feature_a' => true],
        ]);

        $response->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_destroy_deletes_tenant(): void
    {
        $deleteService = $this->createMock(DeleteTenantServiceInterface::class);
        $deleteService->expects($this->once())->method('execute');
        $this->app->instance(DeleteTenantServiceInterface::class, $deleteService);

        $findAttachmentService = $this->createMock(FindTenantAttachmentsServiceInterface::class);
        $this->app->instance(FindTenantAttachmentsServiceInterface::class, $findAttachmentService);

        $createService = $this->createMock(CreateTenantServiceInterface::class);
        $this->app->instance(CreateTenantServiceInterface::class, $createService);

        $uploadService = $this->createMock(UploadTenantAttachmentServiceInterface::class);
        $this->app->instance(UploadTenantAttachmentServiceInterface::class, $uploadService);

        $response = $this->actingAsUser()->deleteJson('/api/tenants/1');

        $response->assertOk()->assertJsonPath('message', 'Tenant deleted successfully');
    }

    // ─── TenantPlanController ───────────────────────────────────────────────────

    private function makePlan(): TenantPlan
    {
        return new TenantPlan(
            name: 'Starter',
            slug: 'starter',
            id: 5,
        );
    }

    public function test_plan_index_returns_paginated_plans(): void
    {
        $plan = $this->makePlan();
        $findPlansService = $this->createMock(FindTenantPlansServiceInterface::class);
        $findPlansService->method('paginateActive')->willReturn(
            new LengthAwarePaginator([$plan], 1, 15, 1)
        );
        $this->app->instance(FindTenantPlansServiceInterface::class, $findPlansService);

        $createPlanService = $this->createMock(CreateTenantPlanServiceInterface::class);
        $this->app->instance(CreateTenantPlanServiceInterface::class, $createPlanService);

        $updatePlanService = $this->createMock(UpdateTenantPlanServiceInterface::class);
        $this->app->instance(UpdateTenantPlanServiceInterface::class, $updatePlanService);

        $deletePlanService = $this->createMock(DeleteTenantPlanServiceInterface::class);
        $this->app->instance(DeleteTenantPlanServiceInterface::class, $deletePlanService);

        $response = $this->actingAsUser()->getJson('/api/tenant-plans');

        $response->assertOk()->assertJsonPath('data.0.id', 5);
    }

    public function test_plan_show_returns_plan_resource(): void
    {
        $plan = $this->makePlan();
        $findPlansService = $this->createMock(FindTenantPlansServiceInterface::class);
        $findPlansService->method('find')->willReturn($plan);
        $findPlansService->method('paginateActive')->willReturn(new LengthAwarePaginator([], 0, 15, 1));
        $this->app->instance(FindTenantPlansServiceInterface::class, $findPlansService);

        $createPlanService = $this->createMock(CreateTenantPlanServiceInterface::class);
        $this->app->instance(CreateTenantPlanServiceInterface::class, $createPlanService);

        $updatePlanService = $this->createMock(UpdateTenantPlanServiceInterface::class);
        $this->app->instance(UpdateTenantPlanServiceInterface::class, $updatePlanService);

        $deletePlanService = $this->createMock(DeleteTenantPlanServiceInterface::class);
        $this->app->instance(DeleteTenantPlanServiceInterface::class, $deletePlanService);

        $response = $this->actingAsUser()->getJson('/api/tenant-plans/5');

        $response->assertOk()->assertJsonPath('data.id', 5);
    }

    public function test_plan_store_creates_plan(): void
    {
        $plan = $this->makePlan();
        $findPlansService = $this->createMock(FindTenantPlansServiceInterface::class);
        $this->app->instance(FindTenantPlansServiceInterface::class, $findPlansService);

        $createPlanService = $this->createMock(CreateTenantPlanServiceInterface::class);
        $createPlanService->method('execute')->willReturn($plan);
        $this->app->instance(CreateTenantPlanServiceInterface::class, $createPlanService);

        $updatePlanService = $this->createMock(UpdateTenantPlanServiceInterface::class);
        $this->app->instance(UpdateTenantPlanServiceInterface::class, $updatePlanService);

        $deletePlanService = $this->createMock(DeleteTenantPlanServiceInterface::class);
        $this->app->instance(DeleteTenantPlanServiceInterface::class, $deletePlanService);

        $response = $this->actingAsUser()->postJson('/api/tenant-plans', [
            'name' => 'Starter',
            'price' => '9.99',
            'currency_code' => 'USD',
            'billing_interval' => 'month',
            'is_active' => true,
        ]);

        $response->assertCreated()->assertJsonPath('data.id', 5);
    }

    public function test_plan_update_modifies_plan(): void
    {
        $plan = $this->makePlan();
        $findPlansService = $this->createMock(FindTenantPlansServiceInterface::class);
        $findPlansService->method('find')->willReturn($plan);
        $this->app->instance(FindTenantPlansServiceInterface::class, $findPlansService);

        $createPlanService = $this->createMock(CreateTenantPlanServiceInterface::class);
        $this->app->instance(CreateTenantPlanServiceInterface::class, $createPlanService);

        $updatePlanService = $this->createMock(UpdateTenantPlanServiceInterface::class);
        $updatePlanService->method('execute')->willReturn($plan);
        $this->app->instance(UpdateTenantPlanServiceInterface::class, $updatePlanService);

        $deletePlanService = $this->createMock(DeleteTenantPlanServiceInterface::class);
        $this->app->instance(DeleteTenantPlanServiceInterface::class, $deletePlanService);

        $response = $this->actingAsUser()->patchJson('/api/tenant-plans/5', ['name' => 'Starter Plus']);

        $response->assertOk()->assertJsonPath('data.id', 5);
    }

    public function test_plan_destroy_deletes_plan(): void
    {
        $plan = $this->makePlan();
        $findPlansService = $this->createMock(FindTenantPlansServiceInterface::class);
        $findPlansService->method('find')->willReturn($plan);
        $this->app->instance(FindTenantPlansServiceInterface::class, $findPlansService);

        $createPlanService = $this->createMock(CreateTenantPlanServiceInterface::class);
        $this->app->instance(CreateTenantPlanServiceInterface::class, $createPlanService);

        $updatePlanService = $this->createMock(UpdateTenantPlanServiceInterface::class);
        $this->app->instance(UpdateTenantPlanServiceInterface::class, $updatePlanService);

        $deletePlanService = $this->createMock(DeleteTenantPlanServiceInterface::class);
        $deletePlanService->expects($this->once())->method('execute');
        $this->app->instance(DeleteTenantPlanServiceInterface::class, $deletePlanService);

        $response = $this->actingAsUser()->deleteJson('/api/tenant-plans/5');

        $response->assertOk()->assertJsonPath('message', 'Tenant plan deleted successfully');
    }

    // ─── TenantDomainController ─────────────────────────────────────────────────

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

    public function test_domain_index_returns_paginated_domains(): void
    {
        $domain = $this->makeDomain();
        $findDomainsService = $this->createMock(FindTenantDomainsServiceInterface::class);
        $findDomainsService->method('paginateByTenant')->willReturn(
            new LengthAwarePaginator([$domain], 1, 15, 1)
        );
        $this->app->instance(FindTenantDomainsServiceInterface::class, $findDomainsService);

        $createDomainService = $this->createMock(CreateTenantDomainServiceInterface::class);
        $this->app->instance(CreateTenantDomainServiceInterface::class, $createDomainService);

        $updateDomainService = $this->createMock(UpdateTenantDomainServiceInterface::class);
        $this->app->instance(UpdateTenantDomainServiceInterface::class, $updateDomainService);

        $deleteDomainService = $this->createMock(DeleteTenantDomainServiceInterface::class);
        $this->app->instance(DeleteTenantDomainServiceInterface::class, $deleteDomainService);

        $response = $this->actingAsUser()->getJson('/api/tenants/1/domains');

        $response->assertOk()->assertJsonPath('data.0.id', 10);
    }

    public function test_domain_show_returns_domain_resource(): void
    {
        $domain = $this->makeDomain();
        $findDomainsService = $this->createMock(FindTenantDomainsServiceInterface::class);
        $findDomainsService->method('find')->willReturn($domain);
        $findDomainsService->method('paginateByTenant')->willReturn(new LengthAwarePaginator([], 0, 15, 1));
        $this->app->instance(FindTenantDomainsServiceInterface::class, $findDomainsService);

        $createDomainService = $this->createMock(CreateTenantDomainServiceInterface::class);
        $this->app->instance(CreateTenantDomainServiceInterface::class, $createDomainService);

        $updateDomainService = $this->createMock(UpdateTenantDomainServiceInterface::class);
        $this->app->instance(UpdateTenantDomainServiceInterface::class, $updateDomainService);

        $deleteDomainService = $this->createMock(DeleteTenantDomainServiceInterface::class);
        $this->app->instance(DeleteTenantDomainServiceInterface::class, $deleteDomainService);

        $response = $this->actingAsUser()->getJson('/api/tenants/1/domains/10');

        $response->assertOk()->assertJsonPath('data.id', 10);
    }

    public function test_domain_store_creates_domain(): void
    {
        $domain = $this->makeDomain();
        $findDomainsService = $this->createMock(FindTenantDomainsServiceInterface::class);
        $this->app->instance(FindTenantDomainsServiceInterface::class, $findDomainsService);

        $createDomainService = $this->createMock(CreateTenantDomainServiceInterface::class);
        $createDomainService->method('execute')->willReturn($domain);
        $this->app->instance(CreateTenantDomainServiceInterface::class, $createDomainService);

        $updateDomainService = $this->createMock(UpdateTenantDomainServiceInterface::class);
        $this->app->instance(UpdateTenantDomainServiceInterface::class, $updateDomainService);

        $deleteDomainService = $this->createMock(DeleteTenantDomainServiceInterface::class);
        $this->app->instance(DeleteTenantDomainServiceInterface::class, $deleteDomainService);

        $response = $this->actingAsUser()->postJson('/api/tenants/1/domains', [
            'domain' => 'test.example.com',
            'is_primary' => false,
            'is_verified' => false,
        ]);

        $response->assertCreated()->assertJsonPath('data.id', 10);
    }

    public function test_domain_update_modifies_domain(): void
    {
        $domain = $this->makeDomain();
        $findDomainsService = $this->createMock(FindTenantDomainsServiceInterface::class);
        $findDomainsService->method('find')->willReturn($domain);
        $this->app->instance(FindTenantDomainsServiceInterface::class, $findDomainsService);

        $createDomainService = $this->createMock(CreateTenantDomainServiceInterface::class);
        $this->app->instance(CreateTenantDomainServiceInterface::class, $createDomainService);

        $updateDomainService = $this->createMock(UpdateTenantDomainServiceInterface::class);
        $updateDomainService->method('execute')->willReturn($domain);
        $this->app->instance(UpdateTenantDomainServiceInterface::class, $updateDomainService);

        $deleteDomainService = $this->createMock(DeleteTenantDomainServiceInterface::class);
        $this->app->instance(DeleteTenantDomainServiceInterface::class, $deleteDomainService);

        $response = $this->actingAsUser()->patchJson('/api/tenants/1/domains/10', ['is_primary' => true]);

        $response->assertOk()->assertJsonPath('data.id', 10);
    }

    public function test_domain_destroy_deletes_domain(): void
    {
        $domain = $this->makeDomain();
        $findDomainsService = $this->createMock(FindTenantDomainsServiceInterface::class);
        $findDomainsService->method('find')->willReturn($domain);
        $this->app->instance(FindTenantDomainsServiceInterface::class, $findDomainsService);

        $createDomainService = $this->createMock(CreateTenantDomainServiceInterface::class);
        $this->app->instance(CreateTenantDomainServiceInterface::class, $createDomainService);

        $updateDomainService = $this->createMock(UpdateTenantDomainServiceInterface::class);
        $this->app->instance(UpdateTenantDomainServiceInterface::class, $updateDomainService);

        $deleteDomainService = $this->createMock(DeleteTenantDomainServiceInterface::class);
        $deleteDomainService->expects($this->once())->method('execute');
        $this->app->instance(DeleteTenantDomainServiceInterface::class, $deleteDomainService);

        $response = $this->actingAsUser()->deleteJson('/api/tenants/1/domains/10');

        $response->assertOk()->assertJsonPath('message', 'Tenant domain deleted successfully');
    }

    // ─── TenantSettingController ────────────────────────────────────────────────

    private function makeSetting(): TenantSetting
    {
        return new TenantSetting(
            tenantId: 1,
            key: 'site_name',
            value: ['text' => 'My Site'],
            group: 'general',
            isPublic: true,
            id: 20,
        );
    }

    public function test_setting_index_returns_paginated_settings(): void
    {
        $setting = $this->makeSetting();
        $findSettingsService = $this->createMock(FindTenantSettingsServiceInterface::class);
        $findSettingsService->method('paginateByTenant')->willReturn(
            new LengthAwarePaginator([$setting], 1, 15, 1)
        );
        $this->app->instance(FindTenantSettingsServiceInterface::class, $findSettingsService);

        $createSettingService = $this->createMock(CreateTenantSettingServiceInterface::class);
        $this->app->instance(CreateTenantSettingServiceInterface::class, $createSettingService);

        $updateSettingService = $this->createMock(UpdateTenantSettingServiceInterface::class);
        $this->app->instance(UpdateTenantSettingServiceInterface::class, $updateSettingService);

        $deleteSettingService = $this->createMock(DeleteTenantSettingServiceInterface::class);
        $this->app->instance(DeleteTenantSettingServiceInterface::class, $deleteSettingService);

        $response = $this->actingAsUser()->getJson('/api/tenants/1/settings');

        $response->assertOk()->assertJsonPath('data.0.id', 20);
    }

    public function test_setting_show_returns_setting_resource(): void
    {
        $setting = $this->makeSetting();
        $findSettingsService = $this->createMock(FindTenantSettingsServiceInterface::class);
        $findSettingsService->method('findByTenantAndKey')->willReturn($setting);
        $findSettingsService->method('paginateByTenant')->willReturn(new LengthAwarePaginator([], 0, 15, 1));
        $this->app->instance(FindTenantSettingsServiceInterface::class, $findSettingsService);

        $createSettingService = $this->createMock(CreateTenantSettingServiceInterface::class);
        $this->app->instance(CreateTenantSettingServiceInterface::class, $createSettingService);

        $updateSettingService = $this->createMock(UpdateTenantSettingServiceInterface::class);
        $this->app->instance(UpdateTenantSettingServiceInterface::class, $updateSettingService);

        $deleteSettingService = $this->createMock(DeleteTenantSettingServiceInterface::class);
        $this->app->instance(DeleteTenantSettingServiceInterface::class, $deleteSettingService);

        $response = $this->actingAsUser()->getJson('/api/tenants/1/settings/site_name');

        $response->assertOk()->assertJsonPath('data.id', 20);
    }

    public function test_setting_store_creates_setting(): void
    {
        $setting = $this->makeSetting();
        $findSettingsService = $this->createMock(FindTenantSettingsServiceInterface::class);
        $this->app->instance(FindTenantSettingsServiceInterface::class, $findSettingsService);

        $createSettingService = $this->createMock(CreateTenantSettingServiceInterface::class);
        $createSettingService->method('execute')->willReturn($setting);
        $this->app->instance(CreateTenantSettingServiceInterface::class, $createSettingService);

        $updateSettingService = $this->createMock(UpdateTenantSettingServiceInterface::class);
        $this->app->instance(UpdateTenantSettingServiceInterface::class, $updateSettingService);

        $deleteSettingService = $this->createMock(DeleteTenantSettingServiceInterface::class);
        $this->app->instance(DeleteTenantSettingServiceInterface::class, $deleteSettingService);

        $response = $this->actingAsUser()->postJson('/api/tenants/1/settings', [
            'key' => 'site_name',
            'value' => ['text' => 'My Site'],
            'group' => 'general',
            'is_public' => true,
        ]);

        $response->assertCreated()->assertJsonPath('data.id', 20);
    }

    public function test_setting_update_modifies_setting(): void
    {
        $setting = $this->makeSetting();
        $findSettingsService = $this->createMock(FindTenantSettingsServiceInterface::class);
        $findSettingsService->method('findByTenantAndKey')->willReturn($setting);
        $this->app->instance(FindTenantSettingsServiceInterface::class, $findSettingsService);

        $createSettingService = $this->createMock(CreateTenantSettingServiceInterface::class);
        $this->app->instance(CreateTenantSettingServiceInterface::class, $createSettingService);

        $updateSettingService = $this->createMock(UpdateTenantSettingServiceInterface::class);
        $updateSettingService->method('execute')->willReturn($setting);
        $this->app->instance(UpdateTenantSettingServiceInterface::class, $updateSettingService);

        $deleteSettingService = $this->createMock(DeleteTenantSettingServiceInterface::class);
        $this->app->instance(DeleteTenantSettingServiceInterface::class, $deleteSettingService);

        $response = $this->actingAsUser()->patchJson('/api/tenants/1/settings/site_name', [
            'value' => ['text' => 'Updated'],
            'group' => 'general',
            'is_public' => true,
        ]);

        $response->assertOk()->assertJsonPath('data.id', 20);
    }

    public function test_setting_destroy_deletes_setting(): void
    {
        $setting = $this->makeSetting();
        $findSettingsService = $this->createMock(FindTenantSettingsServiceInterface::class);
        $findSettingsService->method('findByTenantAndKey')->willReturn($setting);
        $this->app->instance(FindTenantSettingsServiceInterface::class, $findSettingsService);

        $createSettingService = $this->createMock(CreateTenantSettingServiceInterface::class);
        $this->app->instance(CreateTenantSettingServiceInterface::class, $createSettingService);

        $updateSettingService = $this->createMock(UpdateTenantSettingServiceInterface::class);
        $this->app->instance(UpdateTenantSettingServiceInterface::class, $updateSettingService);

        $deleteSettingService = $this->createMock(DeleteTenantSettingServiceInterface::class);
        $deleteSettingService->expects($this->once())->method('execute');
        $this->app->instance(DeleteTenantSettingServiceInterface::class, $deleteSettingService);

        $response = $this->actingAsUser()->deleteJson('/api/tenants/1/settings/site_name');

        $response->assertOk()->assertJsonPath('message', 'Tenant setting deleted successfully');
    }

    // ─── TenantAttachmentController ─────────────────────────────────────────────

    private function makeAttachment(): TenantAttachment
    {
        return new TenantAttachment(
            tenantId: 1,
            uuid: 'test-uuid-1234',
            name: 'logo.png',
            filePath: 'tenants/1/logo.png',
            mimeType: 'image/png',
            size: 2048,
            type: 'logo',
            id: 30,
        );
    }

    public function test_attachment_index_returns_paginated_attachments(): void
    {
        $attachment = $this->makeAttachment();
        $findAttachmentsService = $this->createMock(FindTenantAttachmentsServiceInterface::class);
        $findAttachmentsService->method('paginateByTenant')->willReturn(
            new LengthAwarePaginator([$attachment], 1, 15, 1)
        );
        $this->app->instance(FindTenantAttachmentsServiceInterface::class, $findAttachmentsService);

        $uploadService = $this->createMock(UploadTenantAttachmentServiceInterface::class);
        $this->app->instance(UploadTenantAttachmentServiceInterface::class, $uploadService);

        $bulkUploadService = $this->createMock(BulkUploadTenantAttachmentsServiceInterface::class);
        $this->app->instance(BulkUploadTenantAttachmentsServiceInterface::class, $bulkUploadService);

        $deleteAttachmentService = $this->createMock(DeleteTenantAttachmentServiceInterface::class);
        $this->app->instance(DeleteTenantAttachmentServiceInterface::class, $deleteAttachmentService);

        // Also need AttachmentStorageStrategyInterface
        $storageStrategy = $this->createMock(\Modules\Core\Application\Contracts\AttachmentStorageStrategyInterface::class);
        $this->app->instance(\Modules\Core\Application\Contracts\AttachmentStorageStrategyInterface::class, $storageStrategy);

        $createTenantService = $this->createMock(CreateTenantServiceInterface::class);
        $this->app->instance(CreateTenantServiceInterface::class, $createTenantService);

        $uploadTenantService = $this->createMock(UploadTenantAttachmentServiceInterface::class);
        $this->app->instance(UploadTenantAttachmentServiceInterface::class, $uploadTenantService);

        $response = $this->actingAsUser()->getJson('/api/tenants/1/attachments');

        $response->assertOk()->assertJsonPath('data.0.id', 30);
    }

    public function test_attachment_destroy_deletes_attachment(): void
    {
        $attachment = $this->makeAttachment();
        $findAttachmentsService = $this->createMock(FindTenantAttachmentsServiceInterface::class);
        $findAttachmentsService->method('find')->willReturn($attachment);
        $findAttachmentsService->method('paginateByTenant')->willReturn(new LengthAwarePaginator([], 0, 15, 1));
        $this->app->instance(FindTenantAttachmentsServiceInterface::class, $findAttachmentsService);

        $uploadService = $this->createMock(UploadTenantAttachmentServiceInterface::class);
        $this->app->instance(UploadTenantAttachmentServiceInterface::class, $uploadService);

        $bulkUploadService = $this->createMock(BulkUploadTenantAttachmentsServiceInterface::class);
        $this->app->instance(BulkUploadTenantAttachmentsServiceInterface::class, $bulkUploadService);

        $deleteAttachmentService = $this->createMock(DeleteTenantAttachmentServiceInterface::class);
        $deleteAttachmentService->expects($this->once())->method('execute');
        $this->app->instance(DeleteTenantAttachmentServiceInterface::class, $deleteAttachmentService);

        $storageStrategy = $this->createMock(\Modules\Core\Application\Contracts\AttachmentStorageStrategyInterface::class);
        $this->app->instance(\Modules\Core\Application\Contracts\AttachmentStorageStrategyInterface::class, $storageStrategy);

        $createTenantService = $this->createMock(CreateTenantServiceInterface::class);
        $this->app->instance(CreateTenantServiceInterface::class, $createTenantService);

        $response = $this->actingAsUser()->deleteJson('/api/tenants/1/attachments/30');

        $response->assertOk();
    }
}
