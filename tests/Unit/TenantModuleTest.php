<?php
declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Domain\Exceptions\TenantNotFoundException;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;
use Modules\Tenant\Application\DTOs\CreateTenantData;
use Modules\Tenant\Application\Services\GetTenantService;
use Modules\Tenant\Application\Services\DeleteTenantService;

class TenantModuleTest extends TestCase
{
    private function makeTenant(int $id = 1): Tenant
    {
        return new Tenant($id, 'Test Tenant', 'test-tenant', 'trial', null, null, null, new \DateTime(), new \DateTime());
    }

    public function test_tenant_entity_getters(): void
    {
        $tenant = $this->makeTenant();
        $this->assertEquals(1, $tenant->getId());
        $this->assertEquals('Test Tenant', $tenant->getName());
        $this->assertEquals('test-tenant', $tenant->getSlug());
        $this->assertEquals('trial', $tenant->getStatus());
        $this->assertFalse($tenant->isActive());
    }

    public function test_tenant_activate(): void
    {
        $tenant = $this->makeTenant();
        $tenant->activate();
        $this->assertTrue($tenant->isActive());
        $this->assertEquals('active', $tenant->getStatus());
    }

    public function test_tenant_suspend(): void
    {
        $tenant = $this->makeTenant();
        $tenant->activate();
        $tenant->suspend();
        $this->assertFalse($tenant->isActive());
        $this->assertEquals('suspended', $tenant->getStatus());
    }

    public function test_tenant_cancel(): void
    {
        $tenant = $this->makeTenant();
        $tenant->cancel();
        $this->assertEquals('cancelled', $tenant->getStatus());
    }

    public function test_tenant_update_name(): void
    {
        $tenant = $this->makeTenant();
        $tenant->updateName('New Name');
        $this->assertEquals('New Name', $tenant->getName());
    }

    public function test_get_tenant_service_finds_tenant(): void
    {
        /** @var TenantRepositoryInterface&MockObject $repo */
        $repo = $this->createMock(TenantRepositoryInterface::class);
        $repo->expects($this->once())->method('findById')->with(1)->willReturn($this->makeTenant());

        $service = new GetTenantService($repo);
        $result = $service->findById(1);
        $this->assertEquals(1, $result->getId());
    }

    public function test_get_tenant_service_throws_not_found(): void
    {
        /** @var TenantRepositoryInterface&MockObject $repo */
        $repo = $this->createMock(TenantRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $service = new GetTenantService($repo);
        $this->expectException(TenantNotFoundException::class);
        $service->findById(999);
    }

    public function test_delete_tenant_service_throws_not_found(): void
    {
        /** @var TenantRepositoryInterface&MockObject $repo */
        $repo = $this->createMock(TenantRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $service = new DeleteTenantService($repo);
        $this->expectException(TenantNotFoundException::class);
        $service->execute(999);
    }

    public function test_delete_tenant_service_succeeds(): void
    {
        /** @var TenantRepositoryInterface&MockObject $repo */
        $repo = $this->createMock(TenantRepositoryInterface::class);
        $repo->method('findById')->willReturn($this->makeTenant());
        $repo->method('delete')->willReturn(true);

        $service = new DeleteTenantService($repo);
        $result = $service->execute(1);
        $this->assertTrue($result);
    }

    public function test_tenant_dto_from_array(): void
    {
        $data = CreateTenantData::fromArray(['name' => 'Test', 'slug' => 'test', 'status' => 'active']);
        $this->assertEquals('Test', $data->name);
        $this->assertEquals('test', $data->slug);
        $this->assertEquals('active', $data->status);
    }

    public function test_tenant_not_found_exception_message(): void
    {
        $e = new TenantNotFoundException(42);
        $this->assertStringContainsString('42', $e->getMessage());
        $this->assertStringContainsString('Tenant', $e->getMessage());
    }

    // ──────────────────────────────────────────────────────────────────────
    // Tenant – additional tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_tenant_update_settings(): void
    {
        $tenant = $this->makeTenant();
        $settings = ['timezone' => 'UTC', 'currency' => 'USD', 'locale' => 'en_US'];
        $tenant->updateSettings($settings);
        $this->assertEquals($settings, $tenant->getSettings());
    }

    public function test_tenant_update_settings_to_null(): void
    {
        $tenant = $this->makeTenant();
        $tenant->updateSettings(null);
        $this->assertNull($tenant->getSettings());
    }

    public function test_tenant_plan_type(): void
    {
        $tenant = new Tenant(1, 'Acme', 'acme', 'active', 'enterprise', null, null, null, null);
        $this->assertEquals('enterprise', $tenant->getPlanType());
    }

    public function test_tenant_trial_ends_at(): void
    {
        $trialEnd = new \DateTimeImmutable('+30 days');
        $tenant = new Tenant(1, 'Trial Co', 'trial-co', 'active', 'trial', null, $trialEnd, null, null);
        $this->assertEquals($trialEnd, $tenant->getTrialEndsAt());
    }

    public function test_tenant_activate_from_suspended(): void
    {
        $tenant = $this->makeTenant();
        $tenant->suspend();
        $this->assertFalse($tenant->isActive());
        $tenant->activate();
        $this->assertTrue($tenant->isActive());
    }

    public function test_tenant_with_settings(): void
    {
        $settings = ['max_users' => 100, 'storage_gb' => 50];
        $tenant = new Tenant(2, 'Settings Co', 'settings-co', 'active', 'pro', $settings, null, null, null);
        $this->assertEquals($settings, $tenant->getSettings());
    }
}
