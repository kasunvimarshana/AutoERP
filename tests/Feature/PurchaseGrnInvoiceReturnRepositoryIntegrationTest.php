<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Purchase\Application\Contracts\DeleteGrnServiceInterface;
use Modules\Purchase\Application\Contracts\DeletePurchaseInvoiceServiceInterface;
use Modules\Purchase\Application\Contracts\DeletePurchaseReturnServiceInterface;
use Modules\Purchase\Application\Contracts\UpdateGrnServiceInterface;
use Modules\Purchase\Application\Contracts\UpdatePurchaseInvoiceServiceInterface;
use Modules\Purchase\Application\Contracts\UpdatePurchaseReturnServiceInterface;
use Modules\Purchase\Domain\Entities\GrnHeader;
use Modules\Purchase\Domain\Entities\PurchaseInvoice;
use Modules\Purchase\Domain\Entities\PurchaseReturn;
use Modules\Purchase\Domain\Exceptions\GrnNotFoundException;
use Modules\Purchase\Domain\Exceptions\PurchaseInvoiceNotFoundException;
use Modules\Purchase\Domain\Exceptions\PurchaseReturnNotFoundException;
use Modules\Purchase\Domain\RepositoryInterfaces\GrnHeaderRepositoryInterface;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseInvoiceRepositoryInterface;
use Modules\Purchase\Domain\RepositoryInterfaces\PurchaseReturnRepositoryInterface;
use Tests\TestCase;

class PurchaseGrnInvoiceReturnRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId = 1;

    private int $currencyId = 1;

    private int $warehouseId = 1;

    private int $supplierId = 1;

    private int $createdBy = 1;

    private int $productId = 1;

    private int $uomId = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
    }

    // ─── GRN Header Tests ────────────────────────────────────────────────────

    public function test_grn_save_creates_a_new_grn_header(): void
    {
        /** @var GrnHeaderRepositoryInterface $repository */
        $repository = app(GrnHeaderRepositoryInterface::class);

        $grn = new GrnHeader(
            tenantId: $this->tenantId,
            supplierId: $this->supplierId,
            warehouseId: $this->warehouseId,
            grnNumber: 'GRN-TEST-001',
            status: 'draft',
            receivedDate: new \DateTimeImmutable('2026-01-15'),
            currencyId: $this->currencyId,
            exchangeRate: '1.000000',
            createdBy: $this->createdBy,
            subtotal: '100.000000',
            taxTotal: '10.000000',
            grandTotal: '110.000000',
        );

        $saved = $repository->save($grn);

        $this->assertNotNull($saved->getId());
        $this->assertSame($this->tenantId, $saved->getTenantId());
        $this->assertSame($this->supplierId, $saved->getSupplierId());
        $this->assertSame($this->warehouseId, $saved->getWarehouseId());
        $this->assertSame('GRN-TEST-001', $saved->getGrnNumber());
        $this->assertSame('draft', $saved->getStatus());
        $this->assertSame('100.000000', $saved->getSubtotal());
        $this->assertSame('10.000000', $saved->getTaxTotal());
        $this->assertSame('110.000000', $saved->getGrandTotal());
    }

    public function test_grn_save_updates_existing_grn_header(): void
    {
        /** @var GrnHeaderRepositoryInterface $repository */
        $repository = app(GrnHeaderRepositoryInterface::class);

        $created = $repository->save(new GrnHeader(
            tenantId: $this->tenantId,
            supplierId: $this->supplierId,
            warehouseId: $this->warehouseId,
            grnNumber: 'GRN-UPDATE-001',
            status: 'draft',
            receivedDate: new \DateTimeImmutable('2026-01-15'),
            currencyId: $this->currencyId,
            exchangeRate: '1.000000',
            createdBy: $this->createdBy,
        ));

        $this->assertNotNull($created->getId());

        $updated = new GrnHeader(
            tenantId: $this->tenantId,
            supplierId: $this->supplierId,
            warehouseId: $this->warehouseId,
            grnNumber: 'GRN-UPDATE-001',
            status: 'posted',
            receivedDate: new \DateTimeImmutable('2026-01-16'),
            currencyId: $this->currencyId,
            exchangeRate: '1.000000',
            createdBy: $this->createdBy,
            subtotal: '200.000000',
            grandTotal: '200.000000',
            id: $created->getId(),
        );

        $saved = $repository->save($updated);

        $this->assertSame($created->getId(), $saved->getId());
        $this->assertSame('posted', $saved->getStatus());
        $this->assertSame('200.000000', $saved->getSubtotal());
        $this->assertSame('200.000000', $saved->getGrandTotal());
    }

    public function test_grn_find_returns_grn_header(): void
    {
        /** @var GrnHeaderRepositoryInterface $repository */
        $repository = app(GrnHeaderRepositoryInterface::class);

        $saved = $repository->save(new GrnHeader(
            tenantId: $this->tenantId,
            supplierId: $this->supplierId,
            warehouseId: $this->warehouseId,
            grnNumber: 'GRN-FIND-001',
            status: 'draft',
            receivedDate: new \DateTimeImmutable('2026-03-01'),
            currencyId: $this->currencyId,
            exchangeRate: '1.000000',
            createdBy: $this->createdBy,
        ));

        $found = $repository->find($saved->getId());

        $this->assertInstanceOf(GrnHeader::class, $found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame('GRN-FIND-001', $found->getGrnNumber());
        $this->assertSame($this->tenantId, $found->getTenantId());
        $this->assertSame($this->supplierId, $found->getSupplierId());
        $this->assertSame($this->warehouseId, $found->getWarehouseId());
    }

    public function test_grn_find_returns_null_for_missing_grn(): void
    {
        /** @var GrnHeaderRepositoryInterface $repository */
        $repository = app(GrnHeaderRepositoryInterface::class);

        $this->assertNull($repository->find(99999));
    }

    public function test_grn_status_transitions(): void
    {
        $grn = new GrnHeader(
            tenantId: $this->tenantId,
            supplierId: $this->supplierId,
            warehouseId: $this->warehouseId,
            grnNumber: 'GRN-STATUS-001',
            status: 'draft',
            receivedDate: new \DateTimeImmutable('2026-05-01'),
            currencyId: $this->currencyId,
            exchangeRate: '1.000000',
            createdBy: $this->createdBy,
        );

        $this->assertSame('draft', $grn->getStatus());

        $grn->post();
        $this->assertSame('posted', $grn->getStatus());
    }

    public function test_grn_update_service_rejects_cross_tenant_mutation(): void
    {
        /** @var GrnHeaderRepositoryInterface $repository */
        $repository = app(GrnHeaderRepositoryInterface::class);
        /** @var UpdateGrnServiceInterface $updateService */
        $updateService = app(UpdateGrnServiceInterface::class);

        $created = $repository->save(new GrnHeader(
            tenantId: $this->tenantId,
            supplierId: $this->supplierId,
            warehouseId: $this->warehouseId,
            grnNumber: 'GRN-CROSS-001',
            status: 'draft',
            receivedDate: new \DateTimeImmutable('2026-04-01'),
            currencyId: $this->currencyId,
            exchangeRate: '1.000000',
            createdBy: $this->createdBy,
        ));

        app()->instance('current_tenant_id', $this->tenantId + 1);

        try {
            $updateService->execute([
                'id' => $created->getId(),
                'tenant_id' => $this->tenantId + 1,
                'supplier_id' => $this->supplierId,
                'warehouse_id' => $this->warehouseId,
                'grn_number' => 'GRN-CROSS-UPDATED',
                'currency_id' => $this->currencyId,
                'received_date' => '2026-04-02',
                'created_by' => $this->createdBy,
            ]);

            $this->fail('Expected cross-tenant GRN update to be rejected.');
        } catch (GrnNotFoundException) {
            $this->assertDatabaseHas('grn_headers', [
                'id' => $created->getId(),
                'tenant_id' => $this->tenantId,
                'grn_number' => 'GRN-CROSS-001',
            ]);
        }
    }

    public function test_grn_delete_service_rejects_cross_tenant_mutation(): void
    {
        /** @var GrnHeaderRepositoryInterface $repository */
        $repository = app(GrnHeaderRepositoryInterface::class);
        /** @var DeleteGrnServiceInterface $deleteService */
        $deleteService = app(DeleteGrnServiceInterface::class);

        $created = $repository->save(new GrnHeader(
            tenantId: $this->tenantId,
            supplierId: $this->supplierId,
            warehouseId: $this->warehouseId,
            grnNumber: 'GRN-DEL-CROSS-001',
            status: 'draft',
            receivedDate: new \DateTimeImmutable('2026-04-05'),
            currencyId: $this->currencyId,
            exchangeRate: '1.000000',
            createdBy: $this->createdBy,
        ));

        app()->instance('current_tenant_id', $this->tenantId + 1);

        try {
            $deleteService->execute(['id' => $created->getId()]);

            $this->fail('Expected cross-tenant GRN delete to be rejected.');
        } catch (GrnNotFoundException) {
            $this->assertDatabaseHas('grn_headers', [
                'id' => $created->getId(),
                'tenant_id' => $this->tenantId,
            ]);
        }
    }

    public function test_grn_save_with_line_via_db(): void
    {
        /** @var GrnHeaderRepositoryInterface $repository */
        $repository = app(GrnHeaderRepositoryInterface::class);

        $saved = $repository->save(new GrnHeader(
            tenantId: $this->tenantId,
            supplierId: $this->supplierId,
            warehouseId: $this->warehouseId,
            grnNumber: 'GRN-LINE-001',
            status: 'draft',
            receivedDate: new \DateTimeImmutable('2026-02-01'),
            currencyId: $this->currencyId,
            exchangeRate: '1.000000',
            createdBy: $this->createdBy,
            subtotal: '50.000000',
            grandTotal: '50.000000',
        ));

        // Seed a warehouse location for the FK constraint
        DB::table('warehouse_locations')->insertOrIgnore([
            'id' => 1,
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'name' => 'Main Location',
            'code' => 'MAIN',
            'type' => 'bin',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('grn_lines')->insert([
            'tenant_id' => $this->tenantId,
            'grn_header_id' => $saved->getId(),
            'product_id' => $this->productId,
            'location_id' => 1,
            'uom_id' => $this->uomId,
            'received_qty' => '5.000000',
            'unit_cost' => '10.000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $lineCount = DB::table('grn_lines')
            ->where('grn_header_id', $saved->getId())
            ->count();

        $this->assertSame(1, $lineCount);
    }

    // ─── Purchase Invoice Tests ───────────────────────────────────────────────

    public function test_invoice_save_creates_a_new_purchase_invoice(): void
    {
        /** @var PurchaseInvoiceRepositoryInterface $repository */
        $repository = app(PurchaseInvoiceRepositoryInterface::class);

        $invoice = new PurchaseInvoice(
            tenantId: $this->tenantId,
            supplierId: $this->supplierId,
            invoiceNumber: 'INV-TEST-001',
            status: 'draft',
            invoiceDate: new \DateTimeImmutable('2026-01-20'),
            dueDate: new \DateTimeImmutable('2026-02-20'),
            currencyId: $this->currencyId,
            exchangeRate: '1.000000',
            subtotal: '300.000000',
            taxTotal: '30.000000',
            grandTotal: '330.000000',
        );

        $saved = $repository->save($invoice);

        $this->assertNotNull($saved->getId());
        $this->assertSame($this->tenantId, $saved->getTenantId());
        $this->assertSame($this->supplierId, $saved->getSupplierId());
        $this->assertSame('INV-TEST-001', $saved->getInvoiceNumber());
        $this->assertSame('draft', $saved->getStatus());
        $this->assertSame('300.000000', $saved->getSubtotal());
        $this->assertSame('30.000000', $saved->getTaxTotal());
        $this->assertSame('330.000000', $saved->getGrandTotal());
    }

    public function test_invoice_save_updates_existing_purchase_invoice(): void
    {
        /** @var PurchaseInvoiceRepositoryInterface $repository */
        $repository = app(PurchaseInvoiceRepositoryInterface::class);

        $created = $repository->save(new PurchaseInvoice(
            tenantId: $this->tenantId,
            supplierId: $this->supplierId,
            invoiceNumber: 'INV-UPDATE-001',
            status: 'draft',
            invoiceDate: new \DateTimeImmutable('2026-01-20'),
            dueDate: new \DateTimeImmutable('2026-02-20'),
            currencyId: $this->currencyId,
            exchangeRate: '1.000000',
        ));

        $this->assertNotNull($created->getId());

        $updated = new PurchaseInvoice(
            tenantId: $this->tenantId,
            supplierId: $this->supplierId,
            invoiceNumber: 'INV-UPDATE-001',
            status: 'approved',
            invoiceDate: new \DateTimeImmutable('2026-01-20'),
            dueDate: new \DateTimeImmutable('2026-02-20'),
            currencyId: $this->currencyId,
            exchangeRate: '1.000000',
            subtotal: '500.000000',
            grandTotal: '500.000000',
            id: $created->getId(),
        );

        $saved = $repository->save($updated);

        $this->assertSame($created->getId(), $saved->getId());
        $this->assertSame('approved', $saved->getStatus());
        $this->assertSame('500.000000', $saved->getGrandTotal());
    }

    public function test_invoice_find_returns_purchase_invoice(): void
    {
        /** @var PurchaseInvoiceRepositoryInterface $repository */
        $repository = app(PurchaseInvoiceRepositoryInterface::class);

        $saved = $repository->save(new PurchaseInvoice(
            tenantId: $this->tenantId,
            supplierId: $this->supplierId,
            invoiceNumber: 'INV-FIND-001',
            status: 'draft',
            invoiceDate: new \DateTimeImmutable('2026-03-01'),
            dueDate: new \DateTimeImmutable('2026-04-01'),
            currencyId: $this->currencyId,
            exchangeRate: '1.000000',
        ));

        $found = $repository->find($saved->getId());

        $this->assertInstanceOf(PurchaseInvoice::class, $found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame('INV-FIND-001', $found->getInvoiceNumber());
        $this->assertSame($this->tenantId, $found->getTenantId());
        $this->assertSame($this->supplierId, $found->getSupplierId());
    }

    public function test_invoice_find_returns_null_for_missing_invoice(): void
    {
        /** @var PurchaseInvoiceRepositoryInterface $repository */
        $repository = app(PurchaseInvoiceRepositoryInterface::class);

        $this->assertNull($repository->find(99999));
    }

    public function test_invoice_balance_due_calculation(): void
    {
        $invoice = new PurchaseInvoice(
            tenantId: $this->tenantId,
            supplierId: $this->supplierId,
            invoiceNumber: 'INV-BAL-001',
            status: 'approved',
            invoiceDate: new \DateTimeImmutable('2026-01-01'),
            dueDate: new \DateTimeImmutable('2026-02-01'),
            currencyId: $this->currencyId,
            exchangeRate: '1.000000',
            grandTotal: '1000.000000',
            paidAmount: '400.000000',
        );

        $this->assertSame('600.000000', $invoice->getBalanceDue());
    }

    public function test_invoice_update_service_rejects_cross_tenant_mutation(): void
    {
        /** @var PurchaseInvoiceRepositoryInterface $repository */
        $repository = app(PurchaseInvoiceRepositoryInterface::class);
        /** @var UpdatePurchaseInvoiceServiceInterface $updateService */
        $updateService = app(UpdatePurchaseInvoiceServiceInterface::class);

        $created = $repository->save(new PurchaseInvoice(
            tenantId: $this->tenantId,
            supplierId: $this->supplierId,
            invoiceNumber: 'INV-CROSS-001',
            status: 'draft',
            invoiceDate: new \DateTimeImmutable('2026-04-01'),
            dueDate: new \DateTimeImmutable('2026-05-01'),
            currencyId: $this->currencyId,
            exchangeRate: '1.000000',
        ));

        app()->instance('current_tenant_id', $this->tenantId + 1);

        try {
            $updateService->execute([
                'id' => $created->getId(),
                'tenant_id' => $this->tenantId + 1,
                'supplier_id' => $this->supplierId,
                'invoice_number' => 'INV-CROSS-UPDATED',
                'currency_id' => $this->currencyId,
                'invoice_date' => '2026-04-02',
                'due_date' => '2026-05-02',
            ]);

            $this->fail('Expected cross-tenant invoice update to be rejected.');
        } catch (PurchaseInvoiceNotFoundException) {
            $this->assertDatabaseHas('purchase_invoices', [
                'id' => $created->getId(),
                'tenant_id' => $this->tenantId,
                'invoice_number' => 'INV-CROSS-001',
            ]);
        }
    }

    public function test_invoice_delete_service_rejects_cross_tenant_mutation(): void
    {
        /** @var PurchaseInvoiceRepositoryInterface $repository */
        $repository = app(PurchaseInvoiceRepositoryInterface::class);
        /** @var DeletePurchaseInvoiceServiceInterface $deleteService */
        $deleteService = app(DeletePurchaseInvoiceServiceInterface::class);

        $created = $repository->save(new PurchaseInvoice(
            tenantId: $this->tenantId,
            supplierId: $this->supplierId,
            invoiceNumber: 'INV-DEL-CROSS-001',
            status: 'draft',
            invoiceDate: new \DateTimeImmutable('2026-04-05'),
            dueDate: new \DateTimeImmutable('2026-05-05'),
            currencyId: $this->currencyId,
            exchangeRate: '1.000000',
        ));

        app()->instance('current_tenant_id', $this->tenantId + 1);

        try {
            $deleteService->execute(['id' => $created->getId()]);

            $this->fail('Expected cross-tenant invoice delete to be rejected.');
        } catch (PurchaseInvoiceNotFoundException) {
            $this->assertDatabaseHas('purchase_invoices', [
                'id' => $created->getId(),
                'tenant_id' => $this->tenantId,
            ]);
        }
    }

    // ─── Purchase Return Tests ────────────────────────────────────────────────

    public function test_return_save_creates_a_new_purchase_return(): void
    {
        /** @var PurchaseReturnRepositoryInterface $repository */
        $repository = app(PurchaseReturnRepositoryInterface::class);

        $return = new PurchaseReturn(
            tenantId: $this->tenantId,
            supplierId: $this->supplierId,
            returnNumber: 'RTV-TEST-001',
            status: 'draft',
            returnDate: new \DateTimeImmutable('2026-01-25'),
            currencyId: $this->currencyId,
            exchangeRate: '1.000000',
            subtotal: '75.000000',
            grandTotal: '75.000000',
            discountTotal: '5.000000',
        );

        $saved = $repository->save($return);

        $this->assertNotNull($saved->getId());
        $this->assertSame($this->tenantId, $saved->getTenantId());
        $this->assertSame($this->supplierId, $saved->getSupplierId());
        $this->assertSame('RTV-TEST-001', $saved->getReturnNumber());
        $this->assertSame('draft', $saved->getStatus());
        $this->assertSame('75.000000', $saved->getSubtotal());
        $this->assertSame('75.000000', $saved->getGrandTotal());
        $this->assertSame('5.000000', $saved->getDiscountTotal());
    }

    public function test_return_save_updates_existing_purchase_return(): void
    {
        /** @var PurchaseReturnRepositoryInterface $repository */
        $repository = app(PurchaseReturnRepositoryInterface::class);

        $created = $repository->save(new PurchaseReturn(
            tenantId: $this->tenantId,
            supplierId: $this->supplierId,
            returnNumber: 'RTV-UPDATE-001',
            status: 'draft',
            returnDate: new \DateTimeImmutable('2026-01-25'),
            currencyId: $this->currencyId,
            exchangeRate: '1.000000',
        ));

        $this->assertNotNull($created->getId());

        $updated = new PurchaseReturn(
            tenantId: $this->tenantId,
            supplierId: $this->supplierId,
            returnNumber: 'RTV-UPDATE-001',
            status: 'approved',
            returnDate: new \DateTimeImmutable('2026-01-26'),
            currencyId: $this->currencyId,
            exchangeRate: '1.000000',
            subtotal: '150.000000',
            grandTotal: '150.000000',
            id: $created->getId(),
        );

        $saved = $repository->save($updated);

        $this->assertSame($created->getId(), $saved->getId());
        $this->assertSame('approved', $saved->getStatus());
        $this->assertSame('150.000000', $saved->getGrandTotal());
    }

    public function test_return_find_returns_purchase_return(): void
    {
        /** @var PurchaseReturnRepositoryInterface $repository */
        $repository = app(PurchaseReturnRepositoryInterface::class);

        $saved = $repository->save(new PurchaseReturn(
            tenantId: $this->tenantId,
            supplierId: $this->supplierId,
            returnNumber: 'RTV-FIND-001',
            status: 'draft',
            returnDate: new \DateTimeImmutable('2026-03-10'),
            currencyId: $this->currencyId,
            exchangeRate: '1.000000',
        ));

        $found = $repository->find($saved->getId());

        $this->assertInstanceOf(PurchaseReturn::class, $found);
        $this->assertSame($saved->getId(), $found->getId());
        $this->assertSame('RTV-FIND-001', $found->getReturnNumber());
        $this->assertSame($this->tenantId, $found->getTenantId());
        $this->assertSame($this->supplierId, $found->getSupplierId());
    }

    public function test_return_find_returns_null_for_missing_return(): void
    {
        /** @var PurchaseReturnRepositoryInterface $repository */
        $repository = app(PurchaseReturnRepositoryInterface::class);

        $this->assertNull($repository->find(99999));
    }

    public function test_return_status_transitions(): void
    {
        $return = new PurchaseReturn(
            tenantId: $this->tenantId,
            supplierId: $this->supplierId,
            returnNumber: 'RTV-STATUS-001',
            status: 'draft',
            returnDate: new \DateTimeImmutable('2026-05-01'),
            currencyId: $this->currencyId,
            exchangeRate: '1.000000',
        );

        $this->assertSame('draft', $return->getStatus());

        $return->post();
        $this->assertSame('approved', $return->getStatus());
    }

    public function test_return_update_service_rejects_cross_tenant_mutation(): void
    {
        /** @var PurchaseReturnRepositoryInterface $repository */
        $repository = app(PurchaseReturnRepositoryInterface::class);
        /** @var UpdatePurchaseReturnServiceInterface $updateService */
        $updateService = app(UpdatePurchaseReturnServiceInterface::class);

        $created = $repository->save(new PurchaseReturn(
            tenantId: $this->tenantId,
            supplierId: $this->supplierId,
            returnNumber: 'RTV-CROSS-001',
            status: 'draft',
            returnDate: new \DateTimeImmutable('2026-04-01'),
            currencyId: $this->currencyId,
            exchangeRate: '1.000000',
        ));

        app()->instance('current_tenant_id', $this->tenantId + 1);

        try {
            $updateService->execute([
                'id' => $created->getId(),
                'tenant_id' => $this->tenantId + 1,
                'supplier_id' => $this->supplierId,
                'return_number' => 'RTV-CROSS-UPDATED',
                'currency_id' => $this->currencyId,
                'return_date' => '2026-04-02',
            ]);

            $this->fail('Expected cross-tenant return update to be rejected.');
        } catch (PurchaseReturnNotFoundException) {
            $this->assertDatabaseHas('purchase_returns', [
                'id' => $created->getId(),
                'tenant_id' => $this->tenantId,
                'return_number' => 'RTV-CROSS-001',
            ]);
        }
    }

    public function test_return_delete_service_rejects_cross_tenant_mutation(): void
    {
        /** @var PurchaseReturnRepositoryInterface $repository */
        $repository = app(PurchaseReturnRepositoryInterface::class);
        /** @var DeletePurchaseReturnServiceInterface $deleteService */
        $deleteService = app(DeletePurchaseReturnServiceInterface::class);

        $created = $repository->save(new PurchaseReturn(
            tenantId: $this->tenantId,
            supplierId: $this->supplierId,
            returnNumber: 'RTV-DEL-CROSS-001',
            status: 'draft',
            returnDate: new \DateTimeImmutable('2026-04-05'),
            currencyId: $this->currencyId,
            exchangeRate: '1.000000',
        ));

        app()->instance('current_tenant_id', $this->tenantId + 1);

        try {
            $deleteService->execute(['id' => $created->getId()]);

            $this->fail('Expected cross-tenant return delete to be rejected.');
        } catch (PurchaseReturnNotFoundException) {
            $this->assertDatabaseHas('purchase_returns', [
                'id' => $created->getId(),
                'tenant_id' => $this->tenantId,
            ]);
        }
    }

    public function test_return_discount_total_persists_and_loads(): void
    {
        /** @var PurchaseReturnRepositoryInterface $repository */
        $repository = app(PurchaseReturnRepositoryInterface::class);

        $saved = $repository->save(new PurchaseReturn(
            tenantId: $this->tenantId,
            supplierId: $this->supplierId,
            returnNumber: 'RTV-DISC-001',
            status: 'draft',
            returnDate: new \DateTimeImmutable('2026-06-01'),
            currencyId: $this->currencyId,
            exchangeRate: '1.000000',
            subtotal: '200.000000',
            grandTotal: '185.000000',
            discountTotal: '15.000000',
        ));

        $found = $repository->find($saved->getId());

        $this->assertInstanceOf(PurchaseReturn::class, $found);
        $this->assertSame('15.000000', $found->getDiscountTotal());
        $this->assertSame('185.000000', $found->getGrandTotal());
    }

    // ─── Reference Data Seeding ───────────────────────────────────────────────

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

        DB::table('suppliers')->insert([
            'id' => $this->supplierId,
            'tenant_id' => $this->tenantId,
            'org_unit_id' => null,
            'user_id' => null,
            'supplier_code' => 'SUP-001',
            'name' => 'Test Supplier',
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

        DB::table('units_of_measure')->insert([
            'id' => $this->uomId,
            'tenant_id' => $this->tenantId,
            'name' => 'Each',
            'symbol' => 'EA',
            'type' => 'unit',
            'is_base' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('products')->insert([
            'id' => $this->productId,
            'tenant_id' => $this->tenantId,
            'category_id' => null,
            'brand_id' => null,
            'org_unit_id' => null,
            'type' => 'physical',
            'name' => 'Test Product',
            'slug' => 'test-product',
            'sku' => 'TEST-SKU-001',
            'description' => null,
            'image_path' => null,
            'base_uom_id' => $this->uomId,
            'purchase_uom_id' => null,
            'sales_uom_id' => null,
            'tax_group_id' => null,
            'uom_conversion_factor' => 1,
            'is_batch_tracked' => false,
            'is_lot_tracked' => false,
            'is_serial_tracked' => false,
            'valuation_method' => 'fifo',
            'standard_cost' => null,
            'income_account_id' => null,
            'cogs_account_id' => null,
            'inventory_account_id' => null,
            'expense_account_id' => null,
            'is_active' => true,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
