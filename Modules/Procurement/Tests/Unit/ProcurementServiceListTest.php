<?php

declare(strict_types=1);

namespace Modules\Procurement\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Modules\Procurement\Application\Services\ProcurementService;
use Modules\Procurement\Domain\Contracts\ProcurementRepositoryContract;
use Modules\Procurement\Domain\Contracts\VendorBillRepositoryContract;
use Modules\Procurement\Domain\Contracts\VendorRepositoryContract;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ProcurementService::listOrders() filter-routing logic.
 *
 * The repository is stubbed — no database or Laravel bootstrap required.
 * These tests exercise the routing logic inside listOrders() to validate
 * that the correct repository method is called for each filter combination.
 */
class ProcurementServiceListTest extends TestCase
{
    // -------------------------------------------------------------------------
    // listOrders — routing / filter logic
    // -------------------------------------------------------------------------

    public function test_list_orders_with_vendor_id_filter_calls_find_by_vendor(): void
    {
        $expected = new Collection();

        $repo = $this->createMock(ProcurementRepositoryContract::class);
        $repo->expects($this->once())
            ->method('findByVendor')
            ->with(10)
            ->willReturn($expected);
        $repo->expects($this->never())->method('all');

        $vendorRepo = $this->createMock(VendorRepositoryContract::class);
        $billRepo   = $this->createMock(VendorBillRepositoryContract::class);
        $service    = new ProcurementService($repo, $vendorRepo, $billRepo);
        $result     = $service->listOrders(['vendor_id' => 10]);

        $this->assertSame($expected, $result);
    }

    public function test_list_orders_with_no_filter_calls_all(): void
    {
        $expected = new Collection();

        $repo = $this->createMock(ProcurementRepositoryContract::class);
        $repo->expects($this->once())
            ->method('all')
            ->willReturn($expected);
        $repo->expects($this->never())->method('findByVendor');

        $vendorRepo = $this->createMock(VendorRepositoryContract::class);
        $billRepo   = $this->createMock(VendorBillRepositoryContract::class);
        $service    = new ProcurementService($repo, $vendorRepo, $billRepo);
        $result     = $service->listOrders([]);

        $this->assertSame($expected, $result);
    }

    public function test_list_orders_with_empty_filters_returns_all(): void
    {
        $repo = $this->createMock(ProcurementRepositoryContract::class);
        $repo->method('all')->willReturn(new Collection());

        $vendorRepo = $this->createMock(VendorRepositoryContract::class);
        $billRepo   = $this->createMock(VendorBillRepositoryContract::class);
        $service    = new ProcurementService($repo, $vendorRepo, $billRepo);
        $result     = $service->listOrders();

        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_list_orders_vendor_id_is_cast_to_int(): void
    {
        // The service casts vendor_id with (int), so string '7' becomes 7.
        $expected = new Collection();

        $repo = $this->createMock(ProcurementRepositoryContract::class);
        $repo->expects($this->once())
            ->method('findByVendor')
            ->with(7)
            ->willReturn($expected);

        $vendorRepo = $this->createMock(VendorRepositoryContract::class);
        $billRepo   = $this->createMock(VendorBillRepositoryContract::class);
        $service    = new ProcurementService($repo, $vendorRepo, $billRepo);
        $service->listOrders(['vendor_id' => '7']);
    }

    public function test_list_orders_returns_collection_type(): void
    {
        $repo = $this->createMock(ProcurementRepositoryContract::class);
        $repo->method('all')->willReturn(new Collection());

        $vendorRepo = $this->createMock(VendorRepositoryContract::class);
        $billRepo   = $this->createMock(VendorBillRepositoryContract::class);
        $service    = new ProcurementService($repo, $vendorRepo, $billRepo);
        $result     = $service->listOrders();

        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_list_orders_collection_passthrough(): void
    {
        // Verify that the exact collection returned by the repo is returned to the caller.
        $expected = new Collection([new \stdClass()]);

        $repo = $this->createMock(ProcurementRepositoryContract::class);
        $repo->method('findByVendor')->willReturn($expected);

        $vendorRepo = $this->createMock(VendorRepositoryContract::class);
        $billRepo   = $this->createMock(VendorBillRepositoryContract::class);
        $service    = new ProcurementService($repo, $vendorRepo, $billRepo);
        $result     = $service->listOrders(['vendor_id' => 1]);

        $this->assertCount(1, $result);
        $this->assertSame($expected, $result);
    }
}
