<?php

declare(strict_types=1);

namespace Modules\Procurement\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Modules\Procurement\Application\Services\ProcurementService;
use Modules\Procurement\Domain\Contracts\ProcurementRepositoryContract;
use Modules\Procurement\Domain\Contracts\VendorBillRepositoryContract;
use Modules\Procurement\Domain\Contracts\VendorRepositoryContract;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ProcurementService CRUD methods.
 *
 * Verifies method existence, visibility, and parameter signatures
 * for showPurchaseOrder, updatePurchaseOrder, showVendorBill, and updateVendor.
 * No database or Laravel bootstrap required — uses reflection only.
 */
class ProcurementServiceCrudTest extends TestCase
{
    private function makeService(
        ?ProcurementRepositoryContract $procurementRepo = null,
        ?VendorRepositoryContract $vendorRepo = null,
        ?VendorBillRepositoryContract $vendorBillRepo = null,
    ): ProcurementService {
        return new ProcurementService(
            $procurementRepo ?? $this->createMock(ProcurementRepositoryContract::class),
            $vendorRepo      ?? $this->createMock(VendorRepositoryContract::class),
            $vendorBillRepo  ?? $this->createMock(VendorBillRepositoryContract::class),
        );
    }

    // -------------------------------------------------------------------------
    // showPurchaseOrder
    // -------------------------------------------------------------------------

    public function test_procurement_service_has_show_purchase_order_method(): void
    {
        $this->assertTrue(
            method_exists(ProcurementService::class, 'showPurchaseOrder'),
            'ProcurementService must expose a public showPurchaseOrder() method.'
        );
    }

    public function test_show_purchase_order_is_public(): void
    {
        $reflection = new \ReflectionMethod(ProcurementService::class, 'showPurchaseOrder');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_show_purchase_order_accepts_id_parameter(): void
    {
        $reflection = new \ReflectionMethod(ProcurementService::class, 'showPurchaseOrder');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    public function test_show_purchase_order_delegates_to_repository_find_or_fail(): void
    {
        $expected = $this->createMock(Model::class);
        $repo     = $this->createMock(ProcurementRepositoryContract::class);
        $repo->expects($this->once())
            ->method('findOrFail')
            ->with(5)
            ->willReturn($expected);

        $result = $this->makeService(procurementRepo: $repo)->showPurchaseOrder(5);

        $this->assertSame($expected, $result);
    }

    // -------------------------------------------------------------------------
    // updatePurchaseOrder
    // -------------------------------------------------------------------------

    public function test_procurement_service_has_update_purchase_order_method(): void
    {
        $this->assertTrue(
            method_exists(ProcurementService::class, 'updatePurchaseOrder'),
            'ProcurementService must expose a public updatePurchaseOrder() method.'
        );
    }

    public function test_update_purchase_order_is_public(): void
    {
        $reflection = new \ReflectionMethod(ProcurementService::class, 'updatePurchaseOrder');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_update_purchase_order_accepts_id_and_data_parameters(): void
    {
        $reflection = new \ReflectionMethod(ProcurementService::class, 'updatePurchaseOrder');
        $params     = $reflection->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('data', $params[1]->getName());
    }

    // -------------------------------------------------------------------------
    // showVendorBill
    // -------------------------------------------------------------------------

    public function test_procurement_service_has_show_vendor_bill_method(): void
    {
        $this->assertTrue(
            method_exists(ProcurementService::class, 'showVendorBill'),
            'ProcurementService must expose a public showVendorBill() method.'
        );
    }

    public function test_show_vendor_bill_is_public(): void
    {
        $reflection = new \ReflectionMethod(ProcurementService::class, 'showVendorBill');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_show_vendor_bill_accepts_id_parameter(): void
    {
        $reflection = new \ReflectionMethod(ProcurementService::class, 'showVendorBill');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    public function test_show_vendor_bill_delegates_to_vendor_bill_repository_find_or_fail(): void
    {
        $expected = $this->createMock(Model::class);
        $billRepo = $this->createMock(VendorBillRepositoryContract::class);
        $billRepo->expects($this->once())
            ->method('findOrFail')
            ->with(9)
            ->willReturn($expected);

        $result = $this->makeService(vendorBillRepo: $billRepo)->showVendorBill(9);

        $this->assertSame($expected, $result);
    }

    // -------------------------------------------------------------------------
    // updateVendor
    // -------------------------------------------------------------------------

    public function test_procurement_service_has_update_vendor_method(): void
    {
        $this->assertTrue(
            method_exists(ProcurementService::class, 'updateVendor'),
            'ProcurementService must expose a public updateVendor() method.'
        );
    }

    public function test_update_vendor_is_public(): void
    {
        $reflection = new \ReflectionMethod(ProcurementService::class, 'updateVendor');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_update_vendor_accepts_id_and_data_parameters(): void
    {
        $reflection = new \ReflectionMethod(ProcurementService::class, 'updateVendor');
        $params     = $reflection->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('data', $params[1]->getName());
    }

    public function test_update_vendor_returns_model(): void
    {
        $reflection = new \ReflectionMethod(ProcurementService::class, 'updateVendor');
        $returnType = (string) $reflection->getReturnType();

        $this->assertSame(Model::class, $returnType);
    }
}
