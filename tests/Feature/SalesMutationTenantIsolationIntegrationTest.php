<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Sales\Application\Contracts\ApproveSalesReturnServiceInterface;
use Modules\Sales\Application\Contracts\PostSalesInvoiceServiceInterface;
use Modules\Sales\Application\Contracts\ProcessShipmentServiceInterface;
use Modules\Sales\Application\Contracts\ReceiveSalesReturnServiceInterface;
use Modules\Sales\Domain\Entities\SalesInvoice;
use Modules\Sales\Domain\Entities\SalesReturn;
use Modules\Sales\Domain\Entities\Shipment;
use Modules\Sales\Domain\Exceptions\SalesInvoiceNotFoundException;
use Modules\Sales\Domain\Exceptions\SalesReturnNotFoundException;
use Modules\Sales\Domain\Exceptions\ShipmentNotFoundException;
use Modules\Sales\Domain\RepositoryInterfaces\SalesInvoiceRepositoryInterface;
use Modules\Sales\Domain\RepositoryInterfaces\SalesReturnRepositoryInterface;
use Modules\Sales\Domain\RepositoryInterfaces\ShipmentRepositoryInterface;
use Tests\TestCase;

class SalesMutationTenantIsolationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId = 1;

    private int $currencyId = 1;

    private int $warehouseId = 1;

    private int $customerId = 1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedReferenceData();
    }

    public function testProcessShipmentServiceRejectsCrossTenantMutation(): void
    {
        /** @var ShipmentRepositoryInterface $repository */
        $repository = app(ShipmentRepositoryInterface::class);
        /** @var ProcessShipmentServiceInterface $service */
        $service = app(ProcessShipmentServiceInterface::class);

        $created = $repository->save(new Shipment(
            tenantId: $this->tenantId,
            customerId: $this->customerId,
            warehouseId: $this->warehouseId,
            currencyId: $this->currencyId,
            shipmentNumber: 'SHIP-CROSS-001',
            status: 'draft',
        ));

        app()->instance('current_tenant_id', $this->tenantId + 1);

        try {
            $service->execute(['id' => $created->getId()]);
            $this->fail('Expected cross-tenant shipment processing to be rejected.');
        } catch (ShipmentNotFoundException) {
            $this->assertDatabaseHas('shipments', [
                'id' => $created->getId(),
                'tenant_id' => $this->tenantId,
                'shipment_number' => 'SHIP-CROSS-001',
                'status' => 'draft',
            ]);
        }
    }

    public function testPostSalesInvoiceServiceRejectsCrossTenantMutation(): void
    {
        /** @var SalesInvoiceRepositoryInterface $repository */
        $repository = app(SalesInvoiceRepositoryInterface::class);
        /** @var PostSalesInvoiceServiceInterface $service */
        $service = app(PostSalesInvoiceServiceInterface::class);

        $created = $repository->save(new SalesInvoice(
            tenantId: $this->tenantId,
            customerId: $this->customerId,
            currencyId: $this->currencyId,
            invoiceDate: new \DateTimeImmutable('2026-05-02'),
            dueDate: new \DateTimeImmutable('2026-06-01'),
            invoiceNumber: 'INV-CROSS-001',
            status: 'draft',
        ));

        app()->instance('current_tenant_id', $this->tenantId + 1);

        try {
            $service->execute(['id' => $created->getId()]);
            $this->fail('Expected cross-tenant sales invoice post to be rejected.');
        } catch (SalesInvoiceNotFoundException) {
            $this->assertDatabaseHas('sales_invoices', [
                'id' => $created->getId(),
                'tenant_id' => $this->tenantId,
                'invoice_number' => 'INV-CROSS-001',
                'status' => 'draft',
            ]);
        }
    }

    public function testApproveSalesReturnServiceRejectsCrossTenantMutation(): void
    {
        /** @var SalesReturnRepositoryInterface $repository */
        $repository = app(SalesReturnRepositoryInterface::class);
        /** @var ApproveSalesReturnServiceInterface $service */
        $service = app(ApproveSalesReturnServiceInterface::class);

        $created = $repository->save(new SalesReturn(
            tenantId: $this->tenantId,
            customerId: $this->customerId,
            currencyId: $this->currencyId,
            returnDate: new \DateTimeImmutable('2026-05-02'),
            returnNumber: 'RET-CROSS-001',
            status: 'draft',
        ));

        app()->instance('current_tenant_id', $this->tenantId + 1);

        try {
            $service->execute(['id' => $created->getId()]);
            $this->fail('Expected cross-tenant sales return approval to be rejected.');
        } catch (SalesReturnNotFoundException) {
            $this->assertDatabaseHas('sales_returns', [
                'id' => $created->getId(),
                'tenant_id' => $this->tenantId,
                'return_number' => 'RET-CROSS-001',
                'status' => 'draft',
            ]);
        }
    }

    public function testReceiveSalesReturnServiceRejectsCrossTenantMutation(): void
    {
        /** @var SalesReturnRepositoryInterface $repository */
        $repository = app(SalesReturnRepositoryInterface::class);
        /** @var ReceiveSalesReturnServiceInterface $service */
        $service = app(ReceiveSalesReturnServiceInterface::class);

        $created = $repository->save(new SalesReturn(
            tenantId: $this->tenantId,
            customerId: $this->customerId,
            currencyId: $this->currencyId,
            returnDate: new \DateTimeImmutable('2026-05-02'),
            returnNumber: 'RET-CROSS-002',
            status: 'approved',
        ));

        app()->instance('current_tenant_id', $this->tenantId + 1);

        try {
            $service->execute(['id' => $created->getId()]);
            $this->fail('Expected cross-tenant sales return receive to be rejected.');
        } catch (SalesReturnNotFoundException) {
            $this->assertDatabaseHas('sales_returns', [
                'id' => $created->getId(),
                'tenant_id' => $this->tenantId,
                'return_number' => 'RET-CROSS-002',
                'status' => 'approved',
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

        DB::table('customers')->insert([
            'id' => $this->customerId,
            'tenant_id' => $this->tenantId,
            'user_id' => null,
            'org_unit_id' => null,
            'customer_code' => 'CUST-ISO-001',
            'name' => 'Isolation Customer',
            'type' => 'company',
            'tax_number' => null,
            'registration_number' => null,
            'currency_id' => $this->currencyId,
            'credit_limit' => 0,
            'payment_terms_days' => 30,
            'ar_account_id' => null,
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
            'code' => 'WH-ISO-001',
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
