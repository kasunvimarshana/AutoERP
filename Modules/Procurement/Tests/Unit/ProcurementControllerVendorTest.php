<?php

declare(strict_types=1);

namespace Modules\Procurement\Tests\Unit;

use Modules\Procurement\Interfaces\Http\Controllers\ProcurementController;
use PHPUnit\Framework\TestCase;

/**
 * Structural tests for the procurement vendor and vendor-bill controller methods
 * added to satisfy the full CRUD route surface.
 *
 * Validates method existence, visibility, and return types without
 * requiring database connectivity or full Laravel bootstrap.
 */
class ProcurementControllerVendorTest extends TestCase
{
    // -------------------------------------------------------------------------
    // listVendors
    // -------------------------------------------------------------------------

    public function test_controller_has_list_vendors_method(): void
    {
        $this->assertTrue(
            method_exists(ProcurementController::class, 'listVendors'),
            'ProcurementController must expose a public listVendors() method.'
        );
    }

    public function test_list_vendors_is_public(): void
    {
        $reflection = new \ReflectionMethod(ProcurementController::class, 'listVendors');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_list_vendors_returns_json_response(): void
    {
        $reflection = new \ReflectionMethod(ProcurementController::class, 'listVendors');

        $this->assertSame('Illuminate\Http\JsonResponse', (string) $reflection->getReturnType());
    }

    // -------------------------------------------------------------------------
    // createVendor
    // -------------------------------------------------------------------------

    public function test_controller_has_create_vendor_method(): void
    {
        $this->assertTrue(
            method_exists(ProcurementController::class, 'createVendor'),
            'ProcurementController must expose a public createVendor() method.'
        );
    }

    public function test_create_vendor_is_public(): void
    {
        $reflection = new \ReflectionMethod(ProcurementController::class, 'createVendor');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_create_vendor_returns_json_response(): void
    {
        $reflection = new \ReflectionMethod(ProcurementController::class, 'createVendor');

        $this->assertSame('Illuminate\Http\JsonResponse', (string) $reflection->getReturnType());
    }

    // -------------------------------------------------------------------------
    // showVendor
    // -------------------------------------------------------------------------

    public function test_controller_has_show_vendor_method(): void
    {
        $this->assertTrue(
            method_exists(ProcurementController::class, 'showVendor'),
            'ProcurementController must expose a public showVendor() method.'
        );
    }

    public function test_show_vendor_is_public(): void
    {
        $reflection = new \ReflectionMethod(ProcurementController::class, 'showVendor');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_show_vendor_accepts_id_parameter(): void
    {
        $reflection = new \ReflectionMethod(ProcurementController::class, 'showVendor');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    // -------------------------------------------------------------------------
    // listVendorBills
    // -------------------------------------------------------------------------

    public function test_controller_has_list_vendor_bills_method(): void
    {
        $this->assertTrue(
            method_exists(ProcurementController::class, 'listVendorBills'),
            'ProcurementController must expose a public listVendorBills() method.'
        );
    }

    public function test_list_vendor_bills_is_public(): void
    {
        $reflection = new \ReflectionMethod(ProcurementController::class, 'listVendorBills');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_list_vendor_bills_returns_json_response(): void
    {
        $reflection = new \ReflectionMethod(ProcurementController::class, 'listVendorBills');

        $this->assertSame('Illuminate\Http\JsonResponse', (string) $reflection->getReturnType());
    }

    // -------------------------------------------------------------------------
    // createVendorBill
    // -------------------------------------------------------------------------

    public function test_controller_has_create_vendor_bill_method(): void
    {
        $this->assertTrue(
            method_exists(ProcurementController::class, 'createVendorBill'),
            'ProcurementController must expose a public createVendorBill() method.'
        );
    }

    public function test_create_vendor_bill_is_public(): void
    {
        $reflection = new \ReflectionMethod(ProcurementController::class, 'createVendorBill');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_create_vendor_bill_returns_json_response(): void
    {
        $reflection = new \ReflectionMethod(ProcurementController::class, 'createVendorBill');

        $this->assertSame('Illuminate\Http\JsonResponse', (string) $reflection->getReturnType());
    }
}
