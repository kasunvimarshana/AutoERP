<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Purchase\Application\Contracts\ApprovePurchaseInvoiceServiceInterface;
use Modules\Purchase\Application\Contracts\ConfirmPurchaseOrderServiceInterface;
use Modules\Purchase\Domain\Entities\PurchaseInvoice;
use Modules\Purchase\Domain\Entities\PurchaseOrder;
use Modules\Purchase\Domain\Exceptions\PurchaseInvoiceNotFoundException;
use Modules\Purchase\Domain\Exceptions\PurchaseOrderNotFoundException;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseInvoiceRepositoryInterface;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseOrderRepositoryInterface;
use Tests\TestCase;

class PurchaseMutationTenantIsolationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId = 1;

    private int $currencyId = 1;

    private int $supplierId = 1;

    private int $warehouseId = 1;

    private int $createdBy = 1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedReferenceData();
    }

    public function testConfirmPurchaseOrderServiceRejectsCrossTenantMutation(): void
    {
        /** @var PurchaseOrderRepositoryInterface $repository */
        $repository = app(PurchaseOrderRepositoryInterface::class);
        /** @var ConfirmPurchaseOrderServiceInterface $service */
        $service = app(ConfirmPurchaseOrderServiceInterface::class);

        $created = $repository->save(new PurchaseOrder(
            tenantId: $this->tenantId,
            supplierId: $this->supplierId,
            warehouseId: $this->warehouseId,
            poNumber: 'PO-CROSS-CONFIRM-001',
            status: 'draft',
            currencyId: $this->currencyId,
            exchangeRate: '1.000000',
            orderDate: new \DateTimeImmutable('2026-05-02'),
            createdBy: $this->createdBy,
        ));

        app()->instance('current_tenant_id', $this->tenantId + 1);

        try {
            $service->execute(['id' => $created->getId()]);
            $this->fail('Expected cross-tenant purchase order confirm to be rejected.');
        } catch (PurchaseOrderNotFoundException) {
            $this->assertDatabaseHas('purchase_orders', [
                'id' => $created->getId(),
                'tenant_id' => $this->tenantId,
                'po_number' => 'PO-CROSS-CONFIRM-001',
                'status' => 'draft',
            ]);
        }
    }

    public function testApprovePurchaseInvoiceServiceRejectsCrossTenantMutation(): void
    {
        /** @var PurchaseInvoiceRepositoryInterface $repository */
        $repository = app(PurchaseInvoiceRepositoryInterface::class);
        /** @var ApprovePurchaseInvoiceServiceInterface $service */
        $service = app(ApprovePurchaseInvoiceServiceInterface::class);

        $created = $repository->save(new PurchaseInvoice(
            tenantId: $this->tenantId,
            supplierId: $this->supplierId,
            invoiceNumber: 'PINV-CROSS-001',
            status: 'draft',
            invoiceDate: new \DateTimeImmutable('2026-05-02'),
            dueDate: new \DateTimeImmutable('2026-06-01'),
            currencyId: $this->currencyId,
            exchangeRate: '1.000000',
        ));

        app()->instance('current_tenant_id', $this->tenantId + 1);

        try {
            $service->execute(['id' => $created->getId()]);
            $this->fail('Expected cross-tenant purchase invoice approve to be rejected.');
        } catch (PurchaseInvoiceNotFoundException) {
            $this->assertDatabaseHas('purchase_invoices', [
                'id' => $created->getId(),
                'tenant_id' => $this->tenantId,
                'invoice_number' => 'PINV-CROSS-001',
                'status' => 'draft',
            ]);
        }
    }

    private function seedReferenceData(): void
    {
        DB::table('tenants')->insert([
            'id' => $this->tenantId,
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
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
        ]);

        DB::table('currencies')->insert([
            'id' => $this->currencyId,
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'decimal_places' => 2,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'id' => $this->createdBy,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'purchase-test@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'preferences' => null,
            'address' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('suppliers')->insert([
            'id' => $this->supplierId,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'user_id' => null,
            'supplier_code' => 'SUP-ISO-001',
            'name' => 'Isolation Supplier',
            'type' => 'company',
            'tax_number' => null,
            'registration_number' => null,
            'currency_id' => null,
            'payment_terms_days' => 30,
            'ap_account_id' => null,
            'status' => 'active',
            'notes' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('warehouses')->insert([
            'id' => $this->warehouseId,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'name' => 'Main Warehouse',
            'code' => 'WH-PUR-ISO-001',
            'image_path' => null,
            'type' => 'standard',
            'address_id' => null,
            'is_active' => true,
            'is_default' => true,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
