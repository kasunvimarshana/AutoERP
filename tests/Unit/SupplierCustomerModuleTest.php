<?php
declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Modules\Supplier\Domain\Entities\Supplier;
use Modules\Customer\Domain\Entities\Customer;

class SupplierCustomerModuleTest extends TestCase
{
    private function makeSupplier(array $overrides = []): Supplier
    {
        return new Supplier(
            $overrides['id'] ?? 1, $overrides['tenant_id'] ?? 1,
            $overrides['name'] ?? 'ACME Corp', $overrides['code'] ?? 'SUP-001',
            'acme@example.com', '+1-555-0000', '123 Industrial Ave',
            $overrides['is_active'] ?? true, null, null, null
        );
    }
    private function makeCustomer(array $overrides = []): Customer
    {
        return new Customer(
            $overrides['id'] ?? 1, $overrides['tenant_id'] ?? 1,
            $overrides['name'] ?? 'Globex', $overrides['code'] ?? 'CUS-001',
            'globex@example.com', '+1-555-1111', '456 Commerce Blvd',
            $overrides['is_active'] ?? true, null, null, null
        );
    }

    public function test_supplier_creation(): void
    {
        $sup = $this->makeSupplier();
        $this->assertEquals('SUP-001', $sup->getCode());
        $this->assertEquals('ACME Corp', $sup->getName());
        $this->assertTrue($sup->isActive());
        $this->assertEquals('acme@example.com', $sup->getEmail());
    }

    public function test_customer_creation(): void
    {
        $cus = $this->makeCustomer();
        $this->assertEquals('CUS-001', $cus->getCode());
        $this->assertEquals('Globex', $cus->getName());
        $this->assertTrue($cus->isActive());
    }

    public function test_supplier_deactivate(): void
    {
        $sup = $this->makeSupplier();
        $this->assertTrue($sup->isActive());
        $sup->deactivate();
        $this->assertFalse($sup->isActive());
    }

    public function test_customer_deactivate(): void
    {
        $cus = $this->makeCustomer();
        $this->assertTrue($cus->isActive());
        $cus->deactivate();
        $this->assertFalse($cus->isActive());
    }

    // ──────────────────────────────────────────────────────────────────────
    // Supplier additional tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_supplier_activate(): void
    {
        $sup = $this->makeSupplier(['is_active' => false]);
        $this->assertFalse($sup->isActive());
        $sup->activate();
        $this->assertTrue($sup->isActive());
    }

    public function test_supplier_optional_fields(): void
    {
        $sup = new Supplier(null, 1, 'Minimal Supplier', 'SUP-MIN', null, null, null, true, null, null, null);
        $this->assertNull($sup->getId());
        $this->assertNull($sup->getEmail());
        $this->assertNull($sup->getPhone());
        $this->assertNull($sup->getAddress());
        $this->assertNull($sup->getMetadata());
    }

    public function test_supplier_with_metadata(): void
    {
        $meta = ['payment_terms' => 'Net 30', 'currency' => 'USD'];
        $sup  = new Supplier(1, 1, 'Big Corp', 'SUP-BC', 'big@corp.com', null, null, true, $meta, null, null);
        $this->assertEquals($meta, $sup->getMetadata());
    }

    // ──────────────────────────────────────────────────────────────────────
    // Customer additional tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_customer_activate(): void
    {
        $cus = $this->makeCustomer(['is_active' => false]);
        $this->assertFalse($cus->isActive());
        $cus->activate();
        $this->assertTrue($cus->isActive());
    }

    public function test_customer_optional_fields(): void
    {
        $cus = new Customer(null, 1, 'Anonymous', 'CUS-ANON', null, null, null, true, null, null, null);
        $this->assertNull($cus->getId());
        $this->assertNull($cus->getEmail());
        $this->assertNull($cus->getPhone());
        $this->assertNull($cus->getAddress());
    }

    public function test_customer_with_metadata(): void
    {
        $meta = ['credit_limit' => 5000, 'payment_terms' => 'Net 15'];
        $cus  = new Customer(1, 1, 'VIP Customer', 'CUS-VIP', 'vip@example.com', null, null, true, $meta, null, null);
        $this->assertEquals($meta, $cus->getMetadata());
    }

    public function test_customer_phone_and_address(): void
    {
        $cus = $this->makeCustomer();
        $this->assertEquals('+1-555-1111', $cus->getPhone());
        $this->assertEquals('456 Commerce Blvd', $cus->getAddress());
    }
}
