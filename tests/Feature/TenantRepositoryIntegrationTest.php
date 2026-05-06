<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Domain\Entities\TenantDomain;
use Modules\Tenant\Domain\Entities\TenantPlan;
use Modules\Tenant\Domain\Entities\TenantSetting;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantDomainRepositoryInterface;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantPlanRepositoryInterface;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantSettingRepositoryInterface;
use Modules\Tenant\Domain\ValueObjects\DatabaseConfig;
use Tests\TestCase;

class TenantRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    // ── TenantRepository ──────────────────────────────────────────────────────

    public function test_tenant_save_and_find(): void
    {
        /** @var TenantRepositoryInterface $repository */
        $repository = app(TenantRepositoryInterface::class);

        $saved = $repository->save($this->buildTenant('Acme Corp', 'acme'));

        $found = $repository->find($saved->getId());

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame('Acme Corp', $found->getName());
        $this->assertSame('acme', $found->getSlug());
        $this->assertSame('active', $found->getStatus());
        $this->assertTrue($found->isActive());
    }

    public function test_tenant_find_by_domain(): void
    {
        /** @var TenantRepositoryInterface $repository */
        $repository = app(TenantRepositoryInterface::class);

        $saved = $repository->save($this->buildTenant('Beta Ltd', 'beta', 'beta.example.com'));

        $found = $repository->findByDomain('beta.example.com');
        $notFound = $repository->findByDomain('other.example.com');

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame('beta.example.com', $found->getDomain());
        $this->assertNull($notFound);
    }

    public function test_tenant_update_via_save(): void
    {
        /** @var TenantRepositoryInterface $repository */
        $repository = app(TenantRepositoryInterface::class);

        $saved = $repository->save($this->buildTenant('Old Name', 'old-name'));

        $updated = new Tenant(
            name: 'New Name',
            slug: 'new-name',
            databaseConfig: $this->buildDatabaseConfig(),
            status: 'suspended',
            id: $saved->getId(),
        );
        $repository->save($updated);

        $found = $repository->find($saved->getId());

        $this->assertNotNull($found);
        $this->assertSame('New Name', $found->getName());
        $this->assertSame('new-name', $found->getSlug());
        $this->assertSame('suspended', $found->getStatus());
    }

    // ── TenantSettingRepository ───────────────────────────────────────────────

    public function test_setting_save_and_find_by_key(): void
    {
        /** @var TenantRepositoryInterface $tenantRepo */
        $tenantRepo = app(TenantRepositoryInterface::class);

        /** @var TenantSettingRepositoryInterface $repository */
        $repository = app(TenantSettingRepositoryInterface::class);

        $t1 = $tenantRepo->save($this->buildTenant('Tenant 1', 'tenant-1'));

        $saved = $repository->save(new TenantSetting(
            tenantId: $t1->getId(),
            key: 'logo_url',
            value: ['url' => 'https://cdn.example.com/logo.png'],
            group: 'branding',
            isPublic: true,
        ));

        $found = $repository->findByTenantAndKey($t1->getId(), 'logo_url');
        $notFound = $repository->findByTenantAndKey($t1->getId(), 'missing_key');

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame('logo_url', $found->getKey());
        $this->assertSame('branding', $found->getGroup());
        $this->assertTrue($found->isPublic());
        $this->assertNull($notFound);
    }

    public function test_setting_is_tenant_scoped(): void
    {
        /** @var TenantRepositoryInterface $tenantRepo */
        $tenantRepo = app(TenantRepositoryInterface::class);

        /** @var TenantSettingRepositoryInterface $repository */
        $repository = app(TenantSettingRepositoryInterface::class);

        $t1 = $tenantRepo->save($this->buildTenant('Tenant A', 'tenant-a'));
        $t2 = $tenantRepo->save($this->buildTenant('Tenant B', 'tenant-b'));

        $repository->save(new TenantSetting(tenantId: $t1->getId(), key: 'theme', value: ['color' => 'blue'], group: 'ui'));
        $repository->save(new TenantSetting(tenantId: $t2->getId(), key: 'theme', value: ['color' => 'red'], group: 'ui'));

        $foundT1 = $repository->findByTenantAndKey($t1->getId(), 'theme');
        $foundT2 = $repository->findByTenantAndKey($t2->getId(), 'theme');

        $this->assertNotNull($foundT1);
        $this->assertNotNull($foundT2);
        $this->assertNotSame($foundT1->getId(), $foundT2->getId());
        $this->assertSame(['color' => 'blue'], $foundT1->getValue());
        $this->assertSame(['color' => 'red'], $foundT2->getValue());
    }

    public function test_setting_get_by_tenant_with_group_and_visibility_filter(): void
    {
        /** @var TenantRepositoryInterface $tenantRepo */
        $tenantRepo = app(TenantRepositoryInterface::class);

        /** @var TenantSettingRepositoryInterface $repository */
        $repository = app(TenantSettingRepositoryInterface::class);

        $tenant = $tenantRepo->save($this->buildTenant('Filter Tenant', 'filter-tenant'));
        $tid = $tenant->getId();

        $repository->save(new TenantSetting(tenantId: $tid, key: 'key1', value: null, group: 'branding', isPublic: true));
        $repository->save(new TenantSetting(tenantId: $tid, key: 'key2', value: null, group: 'branding', isPublic: false));
        $repository->save(new TenantSetting(tenantId: $tid, key: 'key3', value: null, group: 'security', isPublic: true));

        $all = collect($repository->getByTenant($tid));
        $brandingOnly = collect($repository->getByTenant($tid, group: 'branding'));
        $publicOnly = collect($repository->getByTenant($tid, isPublic: true));
        $brandingPublic = collect($repository->getByTenant($tid, group: 'branding', isPublic: true));

        $this->assertCount(3, $all);
        $this->assertCount(2, $brandingOnly);
        $this->assertCount(2, $publicOnly);
        $this->assertCount(1, $brandingPublic);
    }

    // ── TenantPlanRepository ──────────────────────────────────────────────────

    public function test_plan_save_and_find_by_slug(): void
    {
        /** @var TenantPlanRepositoryInterface $repository */
        $repository = app(TenantPlanRepositoryInterface::class);

        $saved = $repository->save(new TenantPlan(
            name: 'Starter',
            slug: 'starter',
            price: '9.990000',
            currencyCode: 'USD',
            billingInterval: 'month',
            isActive: true,
        ));

        $found = $repository->findBySlug('starter');
        $notFound = $repository->findBySlug('enterprise');

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame('Starter', $found->getName());
        $this->assertSame('starter', $found->getSlug());
        $this->assertNull($notFound);
    }

    public function test_plan_get_active_with_billing_interval_filter(): void
    {
        /** @var TenantPlanRepositoryInterface $repository */
        $repository = app(TenantPlanRepositoryInterface::class);

        $repository->save(new TenantPlan(name: 'Monthly Basic', slug: 'monthly-basic', billingInterval: 'month', isActive: true));
        $repository->save(new TenantPlan(name: 'Annual Basic', slug: 'annual-basic', billingInterval: 'year', isActive: true));
        $repository->save(new TenantPlan(name: 'Monthly Pro', slug: 'monthly-pro', billingInterval: 'month', isActive: true));
        $repository->save(new TenantPlan(name: 'Inactive Plan', slug: 'inactive', billingInterval: 'month', isActive: false));

        $allActive = collect($repository->getActive());
        $monthlyOnly = collect($repository->getActive(billingInterval: 'month'));
        $annualOnly = collect($repository->getActive(billingInterval: 'year'));

        $this->assertCount(3, $allActive);
        $this->assertCount(2, $monthlyOnly);
        $this->assertCount(1, $annualOnly);

        // Inactive plan must not appear
        $slugs = $allActive->map(fn (TenantPlan $p) => $p->getSlug())->toArray();
        $this->assertNotContains('inactive', $slugs);
    }

    // ── TenantDomainRepository ────────────────────────────────────────────────

    public function test_domain_save_and_find_by_domain(): void
    {
        /** @var TenantRepositoryInterface $tenantRepo */
        $tenantRepo = app(TenantRepositoryInterface::class);

        /** @var TenantDomainRepositoryInterface $repository */
        $repository = app(TenantDomainRepositoryInterface::class);

        $tenant = $tenantRepo->save($this->buildTenant('Domain Tenant', 'domain-tenant'));

        $saved = $repository->save(new TenantDomain(
            tenantId: $tenant->getId(),
            domain: 'shop.example.com',
            isPrimary: true,
            isVerified: true,
            verifiedAt: new \DateTimeImmutable('2025-01-15'),
        ));

        $found = $repository->findByDomain('shop.example.com');
        $notFound = $repository->findByDomain('unknown.example.com');

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame('shop.example.com', $found->getDomain());
        $this->assertTrue($found->isPrimary());
        $this->assertTrue($found->isVerified());
        $this->assertNull($notFound);
    }

    public function test_domain_find_by_tenant_and_domain(): void
    {
        /** @var TenantRepositoryInterface $tenantRepo */
        $tenantRepo = app(TenantRepositoryInterface::class);

        /** @var TenantDomainRepositoryInterface $repository */
        $repository = app(TenantDomainRepositoryInterface::class);

        $t1 = $tenantRepo->save($this->buildTenant('T1', 't1'));
        $t2 = $tenantRepo->save($this->buildTenant('T2', 't2'));

        $repository->save(new TenantDomain(tenantId: $t1->getId(), domain: 't1.example.com'));
        $repository->save(new TenantDomain(tenantId: $t2->getId(), domain: 't2.example.com'));

        $foundT1 = $repository->findByTenantAndDomain($t1->getId(), 't1.example.com');
        $foundT2 = $repository->findByTenantAndDomain($t2->getId(), 't2.example.com');

        // Cross-tenant lookup must return null (t2 does not own t1.example.com)
        $crossTenant = $repository->findByTenantAndDomain($t2->getId(), 't1.example.com');
        $notFound = $repository->findByTenantAndDomain($t1->getId(), 'other.example.com');

        $this->assertNotNull($foundT1);
        $this->assertNotNull($foundT2);
        $this->assertSame($t1->getId(), $foundT1->getTenantId());
        $this->assertSame($t2->getId(), $foundT2->getTenantId());
        $this->assertNull($crossTenant);
        $this->assertNull($notFound);
    }

    public function test_domain_get_by_tenant_with_filters(): void
    {
        /** @var TenantRepositoryInterface $tenantRepo */
        $tenantRepo = app(TenantRepositoryInterface::class);

        /** @var TenantDomainRepositoryInterface $repository */
        $repository = app(TenantDomainRepositoryInterface::class);

        $tenant = $tenantRepo->save($this->buildTenant('Multi Domain', 'multi-domain'));
        $tid = $tenant->getId();

        $repository->save(new TenantDomain(tenantId: $tid, domain: 'primary.com', isPrimary: true, isVerified: true));
        $repository->save(new TenantDomain(tenantId: $tid, domain: 'secondary.com', isPrimary: false, isVerified: true));
        $repository->save(new TenantDomain(tenantId: $tid, domain: 'pending.com', isPrimary: false, isVerified: false));

        $all = collect($repository->getByTenant($tid));
        $verifiedOnly = collect($repository->getByTenant($tid, isVerified: true));
        $primaryOnly = collect($repository->getByTenant($tid, isPrimary: true));
        $unverifiedOnly = collect($repository->getByTenant($tid, isVerified: false));

        $this->assertCount(3, $all);
        $this->assertCount(2, $verifiedOnly);
        $this->assertCount(1, $primaryOnly);
        $this->assertCount(1, $unverifiedOnly);
        $this->assertSame('primary.com', $primaryOnly->first()->getDomain());
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function buildTenant(string $name, string $slug, ?string $domain = null): Tenant
    {
        return new Tenant(
            name: $name,
            slug: $slug,
            databaseConfig: $this->buildDatabaseConfig(),
            domain: $domain,
        );
    }

    private function buildDatabaseConfig(): DatabaseConfig
    {
        return new DatabaseConfig(
            driver: 'mysql',
            host: '127.0.0.1',
            port: 3306,
            database: 'erp_test',
            username: 'root',
            password: 'secret',
        );
    }
}
