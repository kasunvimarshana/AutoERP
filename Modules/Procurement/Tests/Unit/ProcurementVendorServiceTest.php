<?php

declare(strict_types=1);

namespace Modules\Procurement\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Modules\Procurement\Application\DTOs\CreateVendorBillDTO;
use Modules\Procurement\Application\DTOs\CreateVendorDTO;
use Modules\Procurement\Application\Services\ProcurementService;
use Modules\Procurement\Domain\Contracts\ProcurementRepositoryContract;
use Modules\Procurement\Domain\Contracts\VendorBillRepositoryContract;
use Modules\Procurement\Domain\Contracts\VendorRepositoryContract;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ProcurementService vendor and vendor-bill management.
 *
 * These tests validate method existence, signatures, DTO payload mapping,
 * and vendor filter-routing logic without hitting the database.
 */
class ProcurementVendorServiceTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Method existence — structural compliance
    // -------------------------------------------------------------------------

    public function test_procurement_service_has_list_vendors_method(): void
    {
        $this->assertTrue(
            method_exists(ProcurementService::class, 'listVendors'),
            'ProcurementService must expose a public listVendors() method.'
        );
    }

    public function test_procurement_service_has_create_vendor_method(): void
    {
        $this->assertTrue(
            method_exists(ProcurementService::class, 'createVendor'),
            'ProcurementService must expose a public createVendor() method.'
        );
    }

    public function test_procurement_service_has_show_vendor_method(): void
    {
        $this->assertTrue(
            method_exists(ProcurementService::class, 'showVendor'),
            'ProcurementService must expose a public showVendor() method.'
        );
    }

    public function test_procurement_service_has_create_vendor_bill_method(): void
    {
        $this->assertTrue(
            method_exists(ProcurementService::class, 'createVendorBill'),
            'ProcurementService must expose a public createVendorBill() method.'
        );
    }

    public function test_procurement_service_has_list_vendor_bills_method(): void
    {
        $this->assertTrue(
            method_exists(ProcurementService::class, 'listVendorBills'),
            'ProcurementService must expose a public listVendorBills() method.'
        );
    }

    // -------------------------------------------------------------------------
    // CreateVendorDTO — payload mapping
    // -------------------------------------------------------------------------

    public function test_create_vendor_dto_hydrates_all_fields(): void
    {
        $dto = CreateVendorDTO::fromArray([
            'name'        => 'Acme Supplies Ltd',
            'email'       => 'vendor@acme.com',
            'phone'       => '+1-800-000-0000',
            'address'     => '123 Industrial Ave',
            'vendor_code' => 'VND-001',
            'is_active'   => true,
        ]);

        $this->assertSame('Acme Supplies Ltd', $dto->name);
        $this->assertSame('vendor@acme.com', $dto->email);
        $this->assertSame('+1-800-000-0000', $dto->phone);
        $this->assertSame('123 Industrial Ave', $dto->address);
        $this->assertSame('VND-001', $dto->vendorCode);
        $this->assertTrue($dto->isActive);
    }

    public function test_create_vendor_dto_optional_fields_default_to_null(): void
    {
        $dto = CreateVendorDTO::fromArray([
            'name' => 'Minimal Vendor',
        ]);

        $this->assertNull($dto->email);
        $this->assertNull($dto->phone);
        $this->assertNull($dto->address);
        $this->assertNull($dto->vendorCode);
        $this->assertTrue($dto->isActive); // default active
    }

    public function test_create_vendor_dto_to_array_round_trip(): void
    {
        $dto  = CreateVendorDTO::fromArray([
            'name'        => 'Test Vendor',
            'vendor_code' => 'TV-99',
            'is_active'   => false,
        ]);
        $data = $dto->toArray();

        $this->assertSame('Test Vendor', $data['name']);
        $this->assertSame('TV-99', $data['vendor_code']);
        $this->assertFalse($data['is_active']);
        $this->assertNull($data['email']);
    }

    // -------------------------------------------------------------------------
    // listVendors — filter-routing logic
    // -------------------------------------------------------------------------

    public function test_list_vendors_with_no_filter_calls_all(): void
    {
        $expected = new Collection();

        $procRepo   = $this->createMock(ProcurementRepositoryContract::class);
        $vendorRepo = $this->createMock(VendorRepositoryContract::class);
        $vendorRepo->expects($this->once())->method('all')->willReturn($expected);
        $vendorRepo->expects($this->never())->method('findActive');
        $billRepo = $this->createMock(VendorBillRepositoryContract::class);

        $service = new ProcurementService($procRepo, $vendorRepo, $billRepo);
        $result  = $service->listVendors();

        $this->assertSame($expected, $result);
    }

    public function test_list_vendors_with_active_only_calls_find_active(): void
    {
        $expected = new Collection();

        $procRepo   = $this->createMock(ProcurementRepositoryContract::class);
        $vendorRepo = $this->createMock(VendorRepositoryContract::class);
        $vendorRepo->expects($this->once())->method('findActive')->willReturn($expected);
        $vendorRepo->expects($this->never())->method('all');
        $billRepo = $this->createMock(VendorBillRepositoryContract::class);

        $service = new ProcurementService($procRepo, $vendorRepo, $billRepo);
        $result  = $service->listVendors(['active_only' => true]);

        $this->assertSame($expected, $result);
    }

    // -------------------------------------------------------------------------
    // CreateVendorBillDTO — payload mapping
    // -------------------------------------------------------------------------

    public function test_create_vendor_bill_dto_hydrates_required_fields(): void
    {
        $dto = CreateVendorBillDTO::fromArray([
            'vendor_id'   => 3,
            'bill_date'   => '2026-03-01',
            'total_amount' => '1500.0000',
        ]);

        $this->assertSame(3, $dto->vendorId);
        $this->assertSame('2026-03-01', $dto->billDate);
        $this->assertSame('1500.0000', $dto->totalAmount);
        $this->assertNull($dto->purchaseOrderId);
        $this->assertNull($dto->dueDate);
        $this->assertNull($dto->notes);
    }

    public function test_create_vendor_bill_dto_purchase_order_id_is_int(): void
    {
        $dto = CreateVendorBillDTO::fromArray([
            'vendor_id'         => 2,
            'purchase_order_id' => '7',
            'bill_date'         => '2026-03-01',
            'total_amount'      => '500.0000',
        ]);

        $this->assertSame(7, $dto->purchaseOrderId);
        $this->assertIsInt($dto->purchaseOrderId);
    }

    // -------------------------------------------------------------------------
    // listVendorBills — filter-routing
    // -------------------------------------------------------------------------

    public function test_list_vendor_bills_with_vendor_id_calls_find_by_vendor(): void
    {
        $expected = new Collection();

        $procRepo   = $this->createMock(ProcurementRepositoryContract::class);
        $vendorRepo = $this->createMock(VendorRepositoryContract::class);
        $billRepo   = $this->createMock(VendorBillRepositoryContract::class);
        $billRepo->expects($this->once())
            ->method('findByVendor')
            ->with(5)
            ->willReturn($expected);
        $billRepo->expects($this->never())->method('all');

        $service = new ProcurementService($procRepo, $vendorRepo, $billRepo);
        $result  = $service->listVendorBills(['vendor_id' => 5]);

        $this->assertSame($expected, $result);
    }

    public function test_list_vendor_bills_with_po_id_calls_find_by_purchase_order(): void
    {
        $expected = new Collection();

        $procRepo   = $this->createMock(ProcurementRepositoryContract::class);
        $vendorRepo = $this->createMock(VendorRepositoryContract::class);
        $billRepo   = $this->createMock(VendorBillRepositoryContract::class);
        $billRepo->expects($this->once())
            ->method('findByPurchaseOrder')
            ->with(12)
            ->willReturn($expected);

        $service = new ProcurementService($procRepo, $vendorRepo, $billRepo);
        $result  = $service->listVendorBills(['purchase_order_id' => 12]);

        $this->assertSame($expected, $result);
    }

    public function test_list_vendor_bills_with_no_filter_calls_all(): void
    {
        $expected = new Collection();

        $procRepo   = $this->createMock(ProcurementRepositoryContract::class);
        $vendorRepo = $this->createMock(VendorRepositoryContract::class);
        $billRepo   = $this->createMock(VendorBillRepositoryContract::class);
        $billRepo->expects($this->once())->method('all')->willReturn($expected);

        $service = new ProcurementService($procRepo, $vendorRepo, $billRepo);
        $result  = $service->listVendorBills();

        $this->assertSame($expected, $result);
    }
}
