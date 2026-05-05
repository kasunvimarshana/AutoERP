<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Tax\Domain\Entities\TaxGroup;
use Modules\Tax\Domain\Entities\TaxRate;
use Modules\Tax\Domain\Entities\TaxRule;
use Modules\Tax\Domain\RepositoryInterfaces\TaxGroupRepositoryInterface;
use Modules\Tax\Domain\RepositoryInterfaces\TaxRateRepositoryInterface;
use Modules\Tax\Domain\RepositoryInterfaces\TaxRuleRepositoryInterface;
use Tests\TestCase;

class TaxRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedReferenceData();
    }

    // ── TaxGroup ───────────────────────────────────────────────────────────────

    public function test_tax_group_save_and_find_by_tenant_and_name(): void
    {
        /** @var TaxGroupRepositoryInterface $repository */
        $repository = app(TaxGroupRepositoryInterface::class);

        $group = $repository->save(new TaxGroup(
            tenantId: 11,
            name: 'Standard VAT',
            description: '20% standard rate',
        ));

        $found = $repository->findByTenantAndName(11, 'Standard VAT');

        $this->assertNotNull($found);
        $this->assertSame($group->getId(), $found->getId());
        $this->assertSame('Standard VAT', $found->getName());
        $this->assertSame('20% standard rate', $found->getDescription());
    }

    public function test_tax_group_find_by_tenant_and_name_is_tenant_scoped(): void
    {
        /** @var TaxGroupRepositoryInterface $repository */
        $repository = app(TaxGroupRepositoryInterface::class);

        $repository->save(new TaxGroup(tenantId: 11, name: 'GST'));
        $repository->save(new TaxGroup(tenantId: 12, name: 'GST'));

        $foundTenant11 = $repository->findByTenantAndName(11, 'GST');
        $foundTenant12 = $repository->findByTenantAndName(12, 'GST');
        $notFound = $repository->findByTenantAndName(11, 'NonExistent');

        $this->assertNotNull($foundTenant11);
        $this->assertNotNull($foundTenant12);
        $this->assertNotSame($foundTenant11->getId(), $foundTenant12->getId());
        $this->assertSame(11, $foundTenant11->getTenantId());
        $this->assertSame(12, $foundTenant12->getTenantId());
        $this->assertNull($notFound);
    }

    // ── TaxRate ────────────────────────────────────────────────────────────────

    public function test_tax_rate_find_by_tenant_group_and_name(): void
    {
        /** @var TaxRateRepositoryInterface $repository */
        $repository = app(TaxRateRepositoryInterface::class);

        $saved = $repository->save(new TaxRate(
            tenantId: 11,
            taxGroupId: 201,
            name: 'Standard 20%',
            rate: '20.000000',
        ));

        $found = $repository->findByTenantGroupAndName(11, 201, 'Standard 20%');
        $notFound = $repository->findByTenantGroupAndName(12, 201, 'Standard 20%');

        $this->assertNotNull($found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertNull($notFound);
    }

    public function test_tax_rate_find_active_by_group_filters_by_date(): void
    {
        /** @var TaxRateRepositoryInterface $repository */
        $repository = app(TaxRateRepositoryInterface::class);

        // Rate active forever
        $always = $repository->save(new TaxRate(
            tenantId: 11,
            taxGroupId: 201,
            name: 'Always Active',
            rate: '5.000000',
            isActive: true,
        ));

        // Rate only valid in 2023
        $repository->save(new TaxRate(
            tenantId: 11,
            taxGroupId: 201,
            name: 'Expired 2023',
            rate: '10.000000',
            isActive: true,
            validFrom: new \DateTimeImmutable('2023-01-01'),
            validTo: new \DateTimeImmutable('2023-12-31'),
        ));

        // Rate valid from 2025 onwards
        $future = $repository->save(new TaxRate(
            tenantId: 11,
            taxGroupId: 201,
            name: 'Future 2025',
            rate: '15.000000',
            isActive: true,
            validFrom: new \DateTimeImmutable('2025-01-01'),
        ));

        // Inactive rate
        $repository->save(new TaxRate(
            tenantId: 11,
            taxGroupId: 201,
            name: 'Inactive',
            rate: '8.000000',
            isActive: false,
        ));

        $results = $repository->findActiveByGroup(11, 201, new \DateTimeImmutable('2025-06-01'));
        $ids = array_map(fn (TaxRate $r) => $r->getId(), $results);

        $this->assertContains($always->getId(), $ids);
        $this->assertContains($future->getId(), $ids);
        $this->assertCount(2, $results);
    }

    // ── TaxRule ────────────────────────────────────────────────────────────────

    public function test_tax_rule_find_best_match_returns_most_specific_rule(): void
    {
        /** @var TaxRuleRepositoryInterface $repository */
        $repository = app(TaxRuleRepositoryInterface::class);

        // Generic rule — no specifics
        $generic = $repository->save(new TaxRule(
            tenantId: 11,
            taxGroupId: 201,
            priority: 0,
        ));

        // More specific — region only
        $repository->save(new TaxRule(
            tenantId: 11,
            taxGroupId: 201,
            region: 'EU',
            priority: 1,
        ));

        // Most specific — region + party_type
        $specific = $repository->save(new TaxRule(
            tenantId: 11,
            taxGroupId: 201,
            partyType: 'customer',
            region: 'EU',
            priority: 2,
        ));

        $best = $repository->findBestMatch(11, null, 'customer', 'EU');

        $this->assertNotNull($best);
        $this->assertSame($specific->getId(), $best->getId());
    }

    public function test_tax_rule_find_best_match_is_tenant_scoped(): void
    {
        /** @var TaxRuleRepositoryInterface $repository */
        $repository = app(TaxRuleRepositoryInterface::class);

        $ruleT11 = $repository->save(new TaxRule(
            tenantId: 11,
            taxGroupId: 201,
            region: 'LK',
            priority: 5,
        ));

        $repository->save(new TaxRule(
            tenantId: 12,
            taxGroupId: 202,
            region: 'LK',
            priority: 10,
        ));

        $bestForT11 = $repository->findBestMatch(11, null, null, 'LK');
        $bestForT12 = $repository->findBestMatch(12, null, null, 'LK');

        $this->assertNotNull($bestForT11);
        $this->assertNotNull($bestForT12);
        $this->assertSame($ruleT11->getId(), $bestForT11->getId());
        $this->assertNotSame($bestForT11->getId(), $bestForT12->getId());
    }

    public function test_tax_rule_find_best_match_returns_null_when_no_match(): void
    {
        /** @var TaxRuleRepositoryInterface $repository */
        $repository = app(TaxRuleRepositoryInterface::class);

        $result = $repository->findBestMatch(11, null, null, null);

        $this->assertNull($result);
    }

    // ── Seed ───────────────────────────────────────────────────────────────────

    private function seedReferenceData(): void
    {
        $this->insertTenant(11);
        $this->insertTenant(12);

        // Pre-insert tax groups as FK anchors for tax rates and rules
        $this->insertTaxGroup(201, 11, 'Group T11');
        $this->insertTaxGroup(202, 12, 'Group T12');
    }

    private function insertTenant(int $tenantId): void
    {
        DB::table('tenants')->insert([
            'id' => $tenantId,
            'name' => 'Tenant '.$tenantId,
            'slug' => 'tenant-'.$tenantId,
            'domain' => null,
            'logo_path' => null,
            'database_config' => null,
            'mail_config' => null,
            'cache_config' => null,
            'queue_config' => null,
            'feature_flags' => null,
            'api_keys' => null,
            'settings' => null,
            'plan' => 'free',
            'tenant_plan_id' => null,
            'status' => 'active',
            'active' => true,
            'trial_ends_at' => null,
            'subscription_ends_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }

    private function insertTaxGroup(int $id, int $tenantId, string $name): void
    {
        DB::table('tax_groups')->insert([
            'id' => $id,
            'tenant_id' => $tenantId,
            'org_unit_id' => null,
            'row_version' => 1,
            'name' => $name,
            'description' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
