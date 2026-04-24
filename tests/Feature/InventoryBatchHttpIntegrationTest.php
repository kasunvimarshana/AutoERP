<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class InventoryBatchHttpIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->ensureAccountsTableExists();
        $this->seedReferenceData();
        $this->bindCrossCuttingDependencies();
        $this->authenticateUser();
    }

    public function test_batch_crud_via_http_uses_real_services_and_persistence(): void
    {
        $store = $this->withHeader('X-Tenant-ID', '95')->postJson('/api/inventory/batches', [
            'tenant_id' => 95,
            'product_id' => 9501,
            'batch_number' => 'HTTP-BATCH-001',
            'lot_number' => 'HTTP-LOT-001',
            'status' => 'active',
            'sales_price' => '19.990000',
        ]);

        $store->assertStatus(HttpResponse::HTTP_CREATED)
            ->assertJsonPath('tenant_id', 95)
            ->assertJsonPath('product_id', 9501)
            ->assertJsonPath('batch_number', 'HTTP-BATCH-001');

        $batchId = (int) $store->json('id');
        $this->assertGreaterThan(0, $batchId);

        $this->assertDatabaseHas('batches', [
            'id' => $batchId,
            'tenant_id' => 95,
            'product_id' => 9501,
            'batch_number' => 'HTTP-BATCH-001',
            'status' => 'active',
        ]);

        $index = $this->withHeader('X-Tenant-ID', '95')
            ->getJson('/api/inventory/batches?tenant_id=95&status=active');

        $index->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonFragment(['batch_number' => 'HTTP-BATCH-001']);

        $show = $this->withHeader('X-Tenant-ID', '95')
            ->getJson('/api/inventory/batches/'.$batchId);

        $show->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('id', $batchId)
            ->assertJsonPath('batch_number', 'HTTP-BATCH-001');

        $update = $this->withHeader('X-Tenant-ID', '95')
            ->putJson('/api/inventory/batches/'.$batchId, [
                'tenant_id' => 95,
                'batch_number' => 'HTTP-BATCH-001-REV',
                'status' => 'quarantine',
                'lot_number' => 'HTTP-LOT-001-REV',
            ]);

        $update->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('id', $batchId)
            ->assertJsonPath('batch_number', 'HTTP-BATCH-001-REV')
            ->assertJsonPath('status', 'quarantine');

        $this->assertDatabaseHas('batches', [
            'id' => $batchId,
            'batch_number' => 'HTTP-BATCH-001-REV',
            'status' => 'quarantine',
        ]);

        $destroy = $this->withHeader('X-Tenant-ID', '95')
            ->deleteJson('/api/inventory/batches/'.$batchId);

        $destroy->assertStatus(HttpResponse::HTTP_NO_CONTENT);

        $this->assertDatabaseMissing('batches', ['id' => $batchId]);

        $showMissing = $this->withHeader('X-Tenant-ID', '95')
            ->getJson('/api/inventory/batches/'.$batchId);

        $showMissing->assertStatus(HttpResponse::HTTP_NOT_FOUND)
            ->assertJsonPath('message', 'Batch not found.');
    }

    public function test_store_validation_failure_returns_unprocessable_entity_and_does_not_persist(): void
    {
        $response = $this->withHeader('X-Tenant-ID', '95')->postJson('/api/inventory/batches', [
            'tenant_id' => 95,
            'product_id' => 9501,
            'batch_number' => '',
            'status' => 'invalid-status',
            'sales_price' => '-1.000000',
        ]);

        $response->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['batch_number', 'status', 'sales_price']);

        $this->assertDatabaseMissing('batches', [
            'tenant_id' => 95,
            'product_id' => 9501,
            'status' => 'invalid-status',
        ]);
    }

    public function test_update_validation_failure_returns_unprocessable_entity_and_does_not_mutate_row(): void
    {
        DB::table('batches')->insert([
            'id' => 9510,
            'tenant_id' => 95,
            'product_id' => 9501,
            'variant_id' => null,
            'batch_number' => 'VALID-BATCH-001',
            'lot_number' => 'VALID-LOT-001',
            'manufacture_date' => null,
            'expiry_date' => null,
            'received_date' => null,
            'supplier_id' => null,
            'status' => 'active',
            'notes' => null,
            'metadata' => null,
            'sales_price' => '5.000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeader('X-Tenant-ID', '95')
            ->putJson('/api/inventory/batches/9510', [
                'tenant_id' => 95,
                'batch_number' => '',
                'status' => 'not-real',
                'sales_price' => '-4.000000',
            ]);

        $response->assertStatus(HttpResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['batch_number', 'status', 'sales_price']);

        $this->assertDatabaseHas('batches', [
            'id' => 9510,
            'batch_number' => 'VALID-BATCH-001',
            'status' => 'active',
        ]);
    }

    private function bindCrossCuttingDependencies(): void
    {
        $authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $authorizationService->method('can')->willReturn(true);
        $this->app->instance(AuthorizationServiceInterface::class, $authorizationService);

        $tenantConfigClient = $this->createMock(TenantConfigClientInterface::class);
        $tenantConfigClient->method('getConfig')->willReturn(null);
        $this->app->instance(TenantConfigClientInterface::class, $tenantConfigClient);

        $tenantConfigManager = $this->createMock(TenantConfigManagerInterface::class);
        $this->app->instance(TenantConfigManagerInterface::class, $tenantConfigManager);
    }

    private function authenticateUser(): void
    {
        $user = new UserModel([
            'id' => 95001,
            'tenant_id' => 95,
            'email' => 'inventory.batch.http@test.com',
            'password' => 'secret',
            'first_name' => 'Inventory',
            'last_name' => 'HttpTester',
        ]);
        $user->setAttribute('id', 95001);
        $user->setAttribute('tenant_id', 95);

        $this->actingAs($user, 'api');
    }

    private function seedReferenceData(): void
    {
        DB::table('tenants')->insert([
            'id' => 95,
            'name' => 'Tenant 95',
            'slug' => 'tenant-95',
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

        DB::table('units_of_measure')->insert([
            'id' => 9501,
            'tenant_id' => 95,
            'name' => 'Each',
            'symbol' => 'EA95',
            'type' => 'unit',
            'is_base' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        DB::table('products')->insert([
            'id' => 9501,
            'tenant_id' => 95,
            'category_id' => null,
            'brand_id' => null,
            'org_unit_id' => null,
            'type' => 'physical',
            'name' => 'HTTP Integration Product',
            'slug' => 'http-integration-product',
            'sku' => 'HTTP-INT-9501',
            'description' => null,
            'base_uom_id' => 9501,
            'purchase_uom_id' => null,
            'sales_uom_id' => null,
            'tax_group_id' => null,
            'uom_conversion_factor' => '1.0000000000',
            'is_batch_tracked' => true,
            'is_lot_tracked' => true,
            'is_serial_tracked' => false,
            'valuation_method' => 'fifo',
            'standard_cost' => null,
            'income_account_id' => null,
            'cogs_account_id' => null,
            'inventory_account_id' => null,
            'expense_account_id' => null,
            'is_active' => true,
            'image_path' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }

    private function ensureAccountsTableExists(): void
    {
        if (Schema::hasTable('accounts')) {
            return;
        }

        Schema::create('accounts', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
        });
    }
}
