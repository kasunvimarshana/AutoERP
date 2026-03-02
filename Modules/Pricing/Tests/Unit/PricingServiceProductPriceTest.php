<?php

declare(strict_types=1);

namespace Modules\Pricing\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Modules\Pricing\Application\DTOs\CreateProductPriceDTO;
use Modules\Pricing\Application\Services\PricingService;
use Modules\Pricing\Domain\Contracts\PricingRepositoryContract;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Structural unit tests for PricingService product-price management methods.
 *
 * Validates method existence, signatures, return types, and public visibility
 * for listProductPrices() and createProductPrice(). No DB required.
 */
class PricingServiceProductPriceTest extends TestCase
{
    // -------------------------------------------------------------------------
    // listProductPrices
    // -------------------------------------------------------------------------

    public function test_list_product_prices_method_exists(): void
    {
        $this->assertTrue(
            method_exists(PricingService::class, 'listProductPrices'),
            'PricingService must expose a listProductPrices() method.'
        );
    }

    public function test_list_product_prices_is_public(): void
    {
        $ref = new ReflectionMethod(PricingService::class, 'listProductPrices');
        $this->assertTrue($ref->isPublic());
    }

    public function test_list_product_prices_accepts_product_id_integer(): void
    {
        $ref    = new ReflectionMethod(PricingService::class, 'listProductPrices');
        $params = $ref->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('productId', $params[0]->getName());
        $this->assertSame('int', $params[0]->getType()?->getName());
    }

    public function test_list_product_prices_return_type_is_collection(): void
    {
        $ref = new ReflectionMethod(PricingService::class, 'listProductPrices');
        $this->assertSame(Collection::class, $ref->getReturnType()?->getName());
    }

    // -------------------------------------------------------------------------
    // createProductPrice
    // -------------------------------------------------------------------------

    public function test_create_product_price_method_exists(): void
    {
        $this->assertTrue(
            method_exists(PricingService::class, 'createProductPrice'),
            'PricingService must expose a createProductPrice() method.'
        );
    }

    public function test_create_product_price_is_public(): void
    {
        $ref = new ReflectionMethod(PricingService::class, 'createProductPrice');
        $this->assertTrue($ref->isPublic());
    }

    public function test_create_product_price_accepts_dto(): void
    {
        $ref    = new ReflectionMethod(PricingService::class, 'createProductPrice');
        $params = $ref->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('dto', $params[0]->getName());
        $this->assertSame(CreateProductPriceDTO::class, $params[0]->getType()?->getName());
    }

    // -------------------------------------------------------------------------
    // DTO selling price stored as string (BCMath safety)
    // -------------------------------------------------------------------------

    public function test_create_product_price_dto_selling_price_is_string(): void
    {
        $dto = CreateProductPriceDTO::fromArray([
            'product_id'    => 1,
            'price_list_id' => 1,
            'uom_id'        => 1,
            'selling_price' => '49.9999',
        ]);

        $this->assertIsString($dto->sellingPrice, 'Selling price must be a string for BCMath compatibility.');
    }

    // -------------------------------------------------------------------------
    // Service instantiation
    // -------------------------------------------------------------------------

    public function test_service_can_be_instantiated_with_repository_contract(): void
    {
        $repository = $this->createStub(PricingRepositoryContract::class);
        $service    = new PricingService($repository);

        $this->assertInstanceOf(PricingService::class, $service);
    }
}
