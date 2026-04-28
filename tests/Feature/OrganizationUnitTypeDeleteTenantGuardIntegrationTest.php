<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\OrganizationUnit\Application\Contracts\DeleteOrganizationUnitTypeServiceInterface;
use Tests\TestCase;

class OrganizationUnitTypeDeleteTenantGuardIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_delete_org_unit_type_rejects_cross_tenant_id(): void
    {
        $this->seedTenants();
        $this->seedTypes();

        /** @var DeleteOrganizationUnitTypeServiceInterface $service */
        $service = app(DeleteOrganizationUnitTypeServiceInterface::class);

        $deleted = $service->execute([
            'id' => 7201,
            'tenant_id' => 11,
        ]);

        $this->assertFalse($deleted);
        $this->assertDatabaseHas('org_unit_types', [
            'id' => 7201,
            'tenant_id' => 12,
            'name' => 'Type T12',
        ]);
    }

    public function test_delete_org_unit_type_allows_matching_tenant(): void
    {
        $this->seedTenants();
        $this->seedTypes();

        /** @var DeleteOrganizationUnitTypeServiceInterface $service */
        $service = app(DeleteOrganizationUnitTypeServiceInterface::class);

        $deleted = $service->execute([
            'id' => 7101,
            'tenant_id' => 11,
        ]);

        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('org_unit_types', ['id' => 7101]);
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

    private function seedTypes(): void
    {
        DB::table('org_unit_types')->insert([
            [
                'id' => 7101,
                'tenant_id' => 11,
                'row_version' => 1,
                'name' => 'Type T11',
                'level' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
            [
                'id' => 7201,
                'tenant_id' => 12,
                'row_version' => 1,
                'name' => 'Type T12',
                'level' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ],
        ]);
    }
}
