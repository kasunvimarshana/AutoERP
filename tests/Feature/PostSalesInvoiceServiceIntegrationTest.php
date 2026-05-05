<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Sales\Application\Contracts\PostSalesInvoiceServiceInterface;
use Modules\Sales\Domain\Entities\SalesInvoice;
use Modules\Sales\Domain\Entities\SalesOrder;
use Modules\Sales\Domain\Events\SalesInvoicePosted;
use Modules\Sales\Domain\RepositoryInterfaces\SalesInvoiceRepositoryInterface;
use Modules\Sales\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;
use Tests\TestCase;

class PostSalesInvoiceServiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId = 1;

    private int $currencyId = 1;

    private int $warehouseId = 1;

    private int $customerId = 1;

    private int $createdBy = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
    }

    public function test_posting_invoice_updates_shipped_sales_order_to_invoiced(): void
    {
        Event::fake([SalesInvoicePosted::class]);

        /** @var SalesOrderRepositoryInterface $soRepo */
        $soRepo = app(SalesOrderRepositoryInterface::class);
        /** @var SalesInvoiceRepositoryInterface $siRepo */
        $siRepo = app(SalesInvoiceRepositoryInterface::class);
        /** @var PostSalesInvoiceServiceInterface $service */
        $service = app(PostSalesInvoiceServiceInterface::class);

        // Create a Sales Order in 'shipped' status
        $salesOrder = $soRepo->save(new SalesOrder(
            tenantId: $this->tenantId,
            customerId: $this->customerId,
            warehouseId: $this->warehouseId,
            currencyId: $this->currencyId,
            orderDate: new \DateTimeImmutable('2026-01-15'),
            soNumber: 'SO-INV-TEST-001',
            status: 'shipped',
            subtotal: '100.000000',
            grandTotal: '100.000000',
            createdBy: $this->createdBy,
        ));

        $soId = (int) $salesOrder->getId();

        // Create a draft Sales Invoice linked to the SO
        $invoice = $siRepo->save(new SalesInvoice(
            tenantId: $this->tenantId,
            customerId: $this->customerId,
            currencyId: $this->currencyId,
            invoiceDate: new \DateTimeImmutable('2026-01-20'),
            dueDate: new \DateTimeImmutable('2026-02-20'),
            salesOrderId: $soId,
            invoiceNumber: 'INV-TEST-001',
            status: 'draft',
            subtotal: '100.000000',
            grandTotal: '100.000000',
        ));

        // Post the invoice
        $service->execute(['id' => $invoice->getId()]);

        // Assert the invoice status is 'sent'
        $updatedInvoice = $siRepo->find((int) $invoice->getId());
        $this->assertNotNull($updatedInvoice);
        $this->assertSame('sent', $updatedInvoice->getStatus());

        // Assert the Sales Order status is 'invoiced'
        $updatedOrder = $soRepo->find($soId);
        $this->assertNotNull($updatedOrder);
        $this->assertSame('invoiced', $updatedOrder->getStatus());

        Event::assertDispatched(SalesInvoicePosted::class, function (SalesInvoicePosted $event) use ($invoice): bool {
            return $event->salesInvoiceId === (int) $invoice->getId();
        });
    }

    public function test_posting_invoice_updates_partial_sales_order_to_invoiced(): void
    {
        Event::fake([SalesInvoicePosted::class]);

        /** @var SalesOrderRepositoryInterface $soRepo */
        $soRepo = app(SalesOrderRepositoryInterface::class);
        /** @var SalesInvoiceRepositoryInterface $siRepo */
        $siRepo = app(SalesInvoiceRepositoryInterface::class);
        /** @var PostSalesInvoiceServiceInterface $service */
        $service = app(PostSalesInvoiceServiceInterface::class);

        // Create a Sales Order in 'partial' status
        $salesOrder = $soRepo->save(new SalesOrder(
            tenantId: $this->tenantId,
            customerId: $this->customerId,
            warehouseId: $this->warehouseId,
            currencyId: $this->currencyId,
            orderDate: new \DateTimeImmutable('2026-01-15'),
            soNumber: 'SO-INV-TEST-002',
            status: 'partial',
            subtotal: '50.000000',
            grandTotal: '50.000000',
            createdBy: $this->createdBy,
        ));

        $soId = (int) $salesOrder->getId();

        $invoice = $siRepo->save(new SalesInvoice(
            tenantId: $this->tenantId,
            customerId: $this->customerId,
            currencyId: $this->currencyId,
            invoiceDate: new \DateTimeImmutable('2026-01-20'),
            dueDate: new \DateTimeImmutable('2026-02-20'),
            salesOrderId: $soId,
            invoiceNumber: 'INV-TEST-002',
            status: 'draft',
            subtotal: '50.000000',
            grandTotal: '50.000000',
        ));

        $service->execute(['id' => $invoice->getId()]);

        $updatedOrder = $soRepo->find($soId);
        $this->assertNotNull($updatedOrder);
        $this->assertSame('invoiced', $updatedOrder->getStatus());
    }

    public function test_posting_invoice_without_sales_order_does_not_fail(): void
    {
        Event::fake([SalesInvoicePosted::class]);

        /** @var SalesInvoiceRepositoryInterface $siRepo */
        $siRepo = app(SalesInvoiceRepositoryInterface::class);
        /** @var PostSalesInvoiceServiceInterface $service */
        $service = app(PostSalesInvoiceServiceInterface::class);

        // Invoice with no linked SO
        $invoice = $siRepo->save(new SalesInvoice(
            tenantId: $this->tenantId,
            customerId: $this->customerId,
            currencyId: $this->currencyId,
            invoiceDate: new \DateTimeImmutable('2026-01-20'),
            dueDate: new \DateTimeImmutable('2026-02-20'),
            invoiceNumber: 'INV-TEST-003',
            status: 'draft',
            subtotal: '200.000000',
            grandTotal: '200.000000',
        ));

        $service->execute(['id' => $invoice->getId()]);

        $updatedInvoice = $siRepo->find((int) $invoice->getId());
        $this->assertNotNull($updatedInvoice);
        $this->assertSame('sent', $updatedInvoice->getStatus());
        $this->assertNull($updatedInvoice->getSalesOrderId());
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
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'preferences' => null,
            'address' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('customers')->insert([
            'id' => $this->customerId,
            'tenant_id' => $this->tenantId,
            'user_id' => null,
            'org_unit_id' => null,
            'customer_code' => 'CUST-001',
            'name' => 'Test Customer',
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
            'code' => 'WH-001',
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
