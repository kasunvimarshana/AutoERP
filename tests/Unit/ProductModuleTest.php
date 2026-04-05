<?php
declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Modules\Product\Domain\Entities\Product;
use Modules\Product\Domain\Entities\ProductCategory;
use Modules\Product\Domain\ValueObjects\ProductType;
use Modules\Product\Domain\Exceptions\ProductNotFoundException;

class ProductModuleTest extends TestCase
{
    private function makeProduct(string $type = 'physical', string $status = 'active'): Product
    {
        return new Product(
            1, 1, 1, 'Widget Pro', 'widget-pro', 'SKU-001',
            new ProductType($type), 'A great widget', $status,
            99.99, 0.1, 0.5, 'pcs', true, false, true,
            10.0, 20.0, null, null, null
        );
    }
    private function makeCategory(): ProductCategory
    {
        return new ProductCategory(1, 1, null, 'Electronics', 'electronics', null, true, 0, null, null);
    }

    public function test_product_type_value_object(): void
    {
        $t = new ProductType('physical');
        $this->assertEquals('physical', $t->getValue());
        $this->assertTrue($t->isPhysical());
        $this->assertFalse($t->isService());
    }

    public function test_product_type_validates_invalid_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ProductType('invalid_type');
    }

    public function test_product_type_service(): void
    {
        $t = new ProductType('service');
        $this->assertTrue($t->isService());
        $this->assertFalse($t->isPhysical());
    }

    public function test_product_type_to_string(): void
    {
        $t = new ProductType('digital');
        $this->assertEquals('digital', (string)$t);
    }

    public function test_product_creation(): void
    {
        $p = $this->makeProduct();
        $this->assertEquals('SKU-001', $p->getSku());
        $this->assertEquals('Widget Pro', $p->getName());
        $this->assertEquals('active', $p->getStatus());
        $this->assertEquals(99.99, $p->getBasePrice());
        $this->assertTrue($p->isTrackable());
        $this->assertTrue($p->isBatchTracked());
    }

    public function test_product_type_is_physical(): void
    {
        $p = $this->makeProduct('physical');
        $this->assertTrue($p->getType()->isPhysical());
    }

    public function test_product_category_creation(): void
    {
        $c = $this->makeCategory();
        $this->assertEquals('Electronics', $c->getName());
        $this->assertEquals('electronics', $c->getSlug());
        $this->assertNull($c->getParentId());
    }

    public function test_product_not_found_exception(): void
    {
        try {
            throw new ProductNotFoundException(42);
        } catch (ProductNotFoundException $e) {
            $this->assertStringContainsString('42', $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // Product entity – lifecycle transitions
    // ──────────────────────────────────────────────────────────────────────

    public function test_product_deactivate(): void
    {
        $p = $this->makeProduct();
        $this->assertTrue($p->isActive());
        $p->deactivate();
        $this->assertFalse($p->isActive());
        $this->assertEquals('inactive', $p->getStatus());
    }

    public function test_product_activate(): void
    {
        $p = $this->makeProduct('physical', 'inactive');
        $p->activate();
        $this->assertTrue($p->isActive());
        $this->assertEquals('active', $p->getStatus());
    }

    public function test_product_discontinue(): void
    {
        $p = $this->makeProduct();
        $p->discontinue();
        $this->assertEquals('discontinued', $p->getStatus());
        $this->assertFalse($p->isActive());
    }

    public function test_product_update_price(): void
    {
        $p = $this->makeProduct();
        $p->updatePrice(149.99);
        $this->assertEquals(149.99, $p->getBasePrice());
    }

    public function test_product_all_types(): void
    {
        foreach (ProductType::VALID as $type) {
            $p = $this->makeProduct($type);
            $this->assertEquals($type, $p->getType()->getValue());
        }
    }

    public function test_product_type_combo(): void
    {
        $t = new ProductType('combo');
        $this->assertTrue($t->isCombo());
    }

    public function test_product_type_variable(): void
    {
        $t = new ProductType('variable');
        $this->assertTrue($t->isVariable());
    }

    public function test_product_type_digital(): void
    {
        $t = new ProductType('digital');
        $this->assertTrue($t->isDigital());
    }

    public function test_product_is_serialized(): void
    {
        $p = new Product(
            2, 1, null, 'Laptop', 'laptop', 'LAPTOP-001',
            new ProductType('physical'), null, 'active',
            1299.99, 0.0, 2.0, 'pcs', true, true, false,
            null, null, null, null, null,
        );
        $this->assertTrue($p->isSerialized());
        $this->assertFalse($p->isBatchTracked());
    }

    public function test_product_optional_fields(): void
    {
        $p = new Product(
            null, 1, null, 'Software', 'software', 'SW-001',
            new ProductType('digital'), null, 'active',
            49.99, 0.0, null, 'license', false, false, false,
            null, null, ['download_url' => 'https://example.com'], null, null,
        );
        $this->assertNull($p->getId());
        $this->assertNull($p->getCategoryId());
        $this->assertNull($p->getWeight());
        $this->assertNull($p->getMinStockLevel());
        $this->assertEquals(['download_url' => 'https://example.com'], $p->getMetadata());
        $this->assertFalse($p->isTrackable());
    }

    // ──────────────────────────────────────────────────────────────────────
    // ProductCategory – lifecycle transitions and hierarchy
    // ──────────────────────────────────────────────────────────────────────

    public function test_product_category_activate_deactivate(): void
    {
        $c = $this->makeCategory();
        $this->assertTrue($c->isActive());
        $c->deactivate();
        $this->assertFalse($c->isActive());
        $c->activate();
        $this->assertTrue($c->isActive());
    }

    public function test_product_category_nested(): void
    {
        $child = new ProductCategory(2, 1, 1, 'Laptops', 'laptops', 'Portable computers', true, 1, null, null);
        $this->assertEquals(1, $child->getParentId());
        $this->assertEquals(1, $child->getLevel());
        $this->assertEquals('Laptops', $child->getName());
    }

    public function test_product_category_with_description(): void
    {
        $c = new ProductCategory(3, 1, null, 'Furniture', 'furniture', 'Home furniture', true, 0, null, null);
        $this->assertEquals('Home furniture', $c->getDescription());
    }
}
