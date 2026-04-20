<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Tax\Application\Contracts\ResolveTaxServiceInterface;
use Tests\TestCase;

class TaxResolveServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolve_uses_best_matching_rule_and_compound_rate_math(): void
    {
        $this->seedTenant(9);

        DB::table('tax_groups')->insert([
            'id' => 200,
            'tenant_id' => 9,
            'name' => 'Standard VAT',
            'description' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('tax_rates')->insert([
            [
                'id' => 301,
                'tenant_id' => 9,
                'tax_group_id' => 200,
                'name' => 'VAT 10%',
                'rate' => '10.000000',
                'type' => 'percentage',
                'account_id' => null,
                'is_compound' => false,
                'is_active' => true,
                'valid_from' => '2024-01-01',
                'valid_to' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 302,
                'tenant_id' => 9,
                'tax_group_id' => 200,
                'name' => 'Compounded Levy 5%',
                'rate' => '5.000000',
                'type' => 'percentage',
                'account_id' => null,
                'is_compound' => true,
                'is_active' => true,
                'valid_from' => '2024-01-01',
                'valid_to' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('tax_rules')->insert([
            [
                'id' => 401,
                'tenant_id' => 9,
                'tax_group_id' => 200,
                'product_category_id' => null,
                'party_type' => null,
                'region' => null,
                'priority' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 402,
                'tenant_id' => 9,
                'tax_group_id' => 200,
                'product_category_id' => null,
                'party_type' => 'customer',
                'region' => 'LK',
                'priority' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        /** @var ResolveTaxServiceInterface $resolveTaxService */
        $resolveTaxService = app(ResolveTaxServiceInterface::class);

        $result = $resolveTaxService->execute([
            'tenant_id' => 9,
            'taxable_amount' => '100.000000',
            'party_type' => 'customer',
            'region' => 'LK',
            'transaction_date' => '2025-01-01',
        ]);

        $this->assertSame(200, $result['tax_group_id']);
        $this->assertSame(402, $result['matched_rule_id']);
        $this->assertCount(2, $result['lines']);

        $this->assertSame('10.000000', $result['lines'][0]['tax_amount']);
        $this->assertSame('5.500000', $result['lines'][1]['tax_amount']);
        $this->assertSame('15.500000', $result['total_tax_amount']);
        $this->assertSame('115.500000', $result['total_amount']);
    }

    private function seedTenant(int $tenantId): void
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
}
