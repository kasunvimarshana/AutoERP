<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Domain\Entities\PaymentTerm;
use Modules\Finance\Domain\RepositoryInterfaces\PaymentTermRepositoryInterface;
use Tests\TestCase;

class FinancePaymentTermRepositoryTenantGuardIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_rejects_cross_tenant_update_attempt_by_id_mismatch(): void
    {
        $this->seedTenants();

        DB::table('payment_terms')->insert([
            [
                'id' => 8101,
                'tenant_id' => 11,
                'org_unit_id' => null,
                'row_version' => 1,
                'name' => 'Net 30 A',
                'description' => null,
                'days' => 30,
                'discount_days' => null,
                'discount_rate' => null,
                'is_default' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'id' => 8201,
                'tenant_id' => 12,
                'org_unit_id' => null,
                'row_version' => 1,
                'name' => 'Net 45 B',
                'description' => null,
                'days' => 45,
                'discount_days' => null,
                'discount_rate' => null,
                'is_default' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
        ]);

        /** @var PaymentTermRepositoryInterface $repository */
        $repository = app(PaymentTermRepositoryInterface::class);

        try {
            $repository->save(new PaymentTerm(
                tenantId: 11,
                name: 'Compromised',
                days: 7,
                isDefault: false,
                isActive: true,
                id: 8201,
                description: 'Should be rejected',
                discountDays: 1,
                discountRate: 1.5,
            ));
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Tenant mismatch for update operation.', $exception->getMessage());
        }

        $tenant12Row = DB::table('payment_terms')->where('id', 8201)->first();
        $this->assertNotNull($tenant12Row);
        $this->assertSame(12, (int) $tenant12Row->tenant_id);
        $this->assertSame('Net 45 B', (string) $tenant12Row->name);
        $this->assertSame(45, (int) $tenant12Row->days);
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
