<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Domain\Entities\ValuationConfig;
use Modules\Inventory\Domain\RepositoryInterfaces\ValuationConfigRepositoryInterface;
use Tests\TestCase;

class InventoryValuationConfigRepositoryTenantGuardIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_does_not_mutate_config_of_another_tenant(): void
    {
        $this->seedTenants();

        DB::table('valuation_configs')->insert([
            [
                'id' => 9101,
                'tenant_id' => 11,
                'org_unit_id' => null,
                'row_version' => 1,
                'warehouse_id' => null,
                'product_id' => null,
                'transaction_type' => null,
                'valuation_method' => 'fifo',
                'allocation_strategy' => 'fifo',
                'is_active' => true,
                'metadata' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 9201,
                'tenant_id' => 12,
                'org_unit_id' => null,
                'row_version' => 1,
                'warehouse_id' => null,
                'product_id' => null,
                'transaction_type' => null,
                'valuation_method' => 'lifo',
                'allocation_strategy' => 'lifo',
                'is_active' => true,
                'metadata' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        /** @var ValuationConfigRepositoryInterface $repository */
        $repository = app(ValuationConfigRepositoryInterface::class);

        // Intentional mismatch: tenant 11 entity points to tenant 12 row id.
        $repository->update(new ValuationConfig(
            tenantId: 11,
            orgUnitId: null,
            warehouseId: null,
            productId: null,
            transactionType: null,
            valuationMethod: 'weighted_average',
            allocationStrategy: 'manual',
            isActive: false,
            metadata: ['source' => 'malicious-update'],
            id: 9201,
        ));

        $tenant12Row = DB::table('valuation_configs')->where('id', 9201)->first();
        $tenant11Row = DB::table('valuation_configs')->where('id', 9101)->first();

        $this->assertNotNull($tenant12Row);
        $this->assertNotNull($tenant11Row);

        $this->assertSame(12, (int) $tenant12Row->tenant_id);
        $this->assertSame('lifo', (string) $tenant12Row->valuation_method);
        $this->assertSame('lifo', (string) $tenant12Row->allocation_strategy);
        $this->assertSame(11, (int) $tenant11Row->tenant_id);
    }

    private function seedTenants(): void
    {
        foreach ([11, 12] as $tenantId) {
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
}
