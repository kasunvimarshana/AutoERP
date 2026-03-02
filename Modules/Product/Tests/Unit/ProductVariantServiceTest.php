<?php

declare(strict_types=1);

namespace Modules\Product\Tests\Unit;

use Modules\Product\Application\Services\ProductService;
use Modules\Product\Domain\Contracts\ProductRepositoryContract;
use Modules\Product\Domain\Contracts\UomRepositoryContract;
use Modules\Product\Domain\Entities\ProductVariant;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ProductService variant management methods.
 *
 * Verifies method existence, visibility, parameter signatures, and return
 * types for createVariant, listVariants, showVariant, and deleteVariant.
 * Pure-PHP — no database or Laravel bootstrap required.
 */
class ProductVariantServiceTest extends TestCase
{
    private function makeService(
        ?ProductRepositoryContract $productRepo = null,
        ?UomRepositoryContract $uomRepo = null,
    ): ProductService {
        return new ProductService(
            $productRepo ?? $this->createMock(ProductRepositoryContract::class),
            $uomRepo ?? $this->createMock(UomRepositoryContract::class),
        );
    }

    // -------------------------------------------------------------------------
    // createVariant — method existence and signature
    // -------------------------------------------------------------------------

    public function test_create_variant_method_exists(): void
    {
        $this->assertTrue(
            method_exists(ProductService::class, 'createVariant'),
            'ProductService must expose a public createVariant() method.'
        );
    }

    public function test_create_variant_is_public(): void
    {
        $reflection = new \ReflectionMethod(ProductService::class, 'createVariant');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_create_variant_accepts_product_id_and_attributes(): void
    {
        $reflection = new \ReflectionMethod(ProductService::class, 'createVariant');
        $params     = $reflection->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('productId', $params[0]->getName());
        $this->assertSame('int', (string) $params[0]->getType());
        $this->assertSame('attributes', $params[1]->getName());
        $this->assertSame('array', (string) $params[1]->getType());
    }

    public function test_create_variant_return_type_is_product_variant(): void
    {
        $reflection = new \ReflectionMethod(ProductService::class, 'createVariant');

        $this->assertStringContainsString('ProductVariant', (string) $reflection->getReturnType());
    }

    // -------------------------------------------------------------------------
    // listVariants — method existence and signature
    // -------------------------------------------------------------------------

    public function test_list_variants_method_exists(): void
    {
        $this->assertTrue(
            method_exists(ProductService::class, 'listVariants'),
            'ProductService must expose a public listVariants() method.'
        );
    }

    public function test_list_variants_is_public(): void
    {
        $reflection = new \ReflectionMethod(ProductService::class, 'listVariants');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_list_variants_accepts_product_id_param(): void
    {
        $reflection = new \ReflectionMethod(ProductService::class, 'listVariants');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('productId', $params[0]->getName());
        $this->assertSame('int', (string) $params[0]->getType());
    }

    public function test_list_variants_is_not_static(): void
    {
        $reflection = new \ReflectionMethod(ProductService::class, 'listVariants');

        $this->assertFalse($reflection->isStatic());
    }

    // -------------------------------------------------------------------------
    // showVariant — method existence and signature
    // -------------------------------------------------------------------------

    public function test_show_variant_method_exists(): void
    {
        $this->assertTrue(
            method_exists(ProductService::class, 'showVariant'),
            'ProductService must expose a public showVariant() method.'
        );
    }

    public function test_show_variant_is_public(): void
    {
        $reflection = new \ReflectionMethod(ProductService::class, 'showVariant');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_show_variant_accepts_variant_id_param(): void
    {
        $reflection = new \ReflectionMethod(ProductService::class, 'showVariant');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('variantId', $params[0]->getName());
        $this->assertSame('int', (string) $params[0]->getType());
    }

    public function test_show_variant_return_type_is_product_variant(): void
    {
        $reflection = new \ReflectionMethod(ProductService::class, 'showVariant');

        $this->assertStringContainsString('ProductVariant', (string) $reflection->getReturnType());
    }

    // -------------------------------------------------------------------------
    // deleteVariant — method existence and signature
    // -------------------------------------------------------------------------

    public function test_delete_variant_method_exists(): void
    {
        $this->assertTrue(
            method_exists(ProductService::class, 'deleteVariant'),
            'ProductService must expose a public deleteVariant() method.'
        );
    }

    public function test_delete_variant_is_public(): void
    {
        $reflection = new \ReflectionMethod(ProductService::class, 'deleteVariant');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_delete_variant_accepts_variant_id_param(): void
    {
        $reflection = new \ReflectionMethod(ProductService::class, 'deleteVariant');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('variantId', $params[0]->getName());
        $this->assertSame('int', (string) $params[0]->getType());
    }

    public function test_delete_variant_return_type_is_bool(): void
    {
        $reflection = new \ReflectionMethod(ProductService::class, 'deleteVariant');

        $this->assertSame('bool', (string) $reflection->getReturnType());
    }

    // -------------------------------------------------------------------------
    // Service instantiation — regression guard
    // -------------------------------------------------------------------------

    public function test_service_can_be_instantiated_with_mocked_contracts(): void
    {
        $service = $this->makeService();

        $this->assertInstanceOf(ProductService::class, $service);
    }

    public function test_existing_create_method_still_present(): void
    {
        $this->assertTrue(method_exists(ProductService::class, 'create'));
        $this->assertTrue(method_exists(ProductService::class, 'list'));
        $this->assertTrue(method_exists(ProductService::class, 'show'));
        $this->assertTrue(method_exists(ProductService::class, 'update'));
        $this->assertTrue(method_exists(ProductService::class, 'delete'));
    }
}
