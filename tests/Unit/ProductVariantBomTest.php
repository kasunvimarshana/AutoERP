<?php
declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Modules\Product\Domain\Entities\ProductVariant;
use Modules\Product\Domain\Entities\ProductComponent;

class ProductVariantBomTest extends TestCase
{
    // ──────────────────────────────────────────────────────────────────────
    // ProductVariant tests (variable products)
    // ──────────────────────────────────────────────────────────────────────

    private function makeVariant(string $status = 'active'): ProductVariant
    {
        return new ProductVariant(
            1, 1, 10,
            'TSHIRT-L-BLUE',
            ['size' => 'L', 'colour' => 'blue'],
            29.99,   // price override
            12.00,   // cost override
            $status,
            null, null,
        );
    }

    public function test_product_variant_creation(): void
    {
        $v = $this->makeVariant();
        $this->assertEquals('TSHIRT-L-BLUE', $v->getSku());
        $this->assertEquals(['size' => 'L', 'colour' => 'blue'], $v->getAttributes());
        $this->assertEquals(29.99, $v->getPriceOverride());
        $this->assertEquals(12.00, $v->getCostOverride());
        $this->assertEquals(10, $v->getProductId());
        $this->assertTrue($v->isActive());
    }

    public function test_product_variant_deactivate(): void
    {
        $v = $this->makeVariant();
        $v->deactivate();
        $this->assertFalse($v->isActive());
        $this->assertEquals('inactive', $v->getStatus());
    }

    public function test_product_variant_reactivate(): void
    {
        $v = $this->makeVariant('inactive');
        $v->activate();
        $this->assertTrue($v->isActive());
    }

    public function test_product_variant_update_price(): void
    {
        $v = $this->makeVariant();
        $v->updatePrice(34.99);
        $this->assertEquals(34.99, $v->getPriceOverride());
    }

    public function test_product_variant_no_price_override(): void
    {
        $v = new ProductVariant(2, 1, 10, 'TSHIRT-S-RED', ['size' => 'S', 'colour' => 'red'], null, null, 'active', null, null);
        $this->assertNull($v->getPriceOverride());
        $this->assertNull($v->getCostOverride());
    }

    public function test_product_variant_attributes_are_flexible(): void
    {
        $v = new ProductVariant(3, 1, 10, 'WINE-750ML', ['volume' => '750ml', 'vintage' => '2021', 'region' => 'Bordeaux'], null, null, 'active', null, null);
        $this->assertArrayHasKey('vintage', $v->getAttributes());
        $this->assertEquals('2021', $v->getAttributes()['vintage']);
    }

    // ──────────────────────────────────────────────────────────────────────
    // ProductComponent tests (combo / Bill of Materials)
    // ──────────────────────────────────────────────────────────────────────

    private function makeComponent(float $qty = 2.0, bool $optional = false): ProductComponent
    {
        return new ProductComponent(
            1, 1,
            100,   // parent product id (Gift Box)
            200,   // component product id (Chocolate Bar)
            $qty,
            'pcs',
            $optional,
            null, null,
        );
    }

    public function test_product_component_creation(): void
    {
        $c = $this->makeComponent();
        $this->assertEquals(100, $c->getParentProductId());
        $this->assertEquals(200, $c->getComponentProductId());
        $this->assertEquals(2.0, $c->getQuantity());
        $this->assertEquals('pcs', $c->getUnit());
        $this->assertFalse($c->isOptional());
    }

    public function test_product_component_optional_flag(): void
    {
        $c = $this->makeComponent(1.0, true);
        $this->assertTrue($c->isOptional());
    }

    public function test_product_component_update_quantity(): void
    {
        $c = $this->makeComponent();
        $c->updateQuantity(5.0);
        $this->assertEquals(5.0, $c->getQuantity());
    }

    public function test_product_component_rejects_non_positive_quantity(): void
    {
        $c = $this->makeComponent();
        $this->expectException(\InvalidArgumentException::class);
        $c->updateQuantity(0.0);
    }

    public function test_product_component_rejects_negative_quantity(): void
    {
        $c = $this->makeComponent();
        $this->expectException(\InvalidArgumentException::class);
        $c->updateQuantity(-1.0);
    }

    public function test_product_component_tenant_scoped(): void
    {
        $c = $this->makeComponent();
        $this->assertEquals(1, $c->getTenantId());
    }
}
