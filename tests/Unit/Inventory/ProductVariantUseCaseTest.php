<?php

namespace Tests\Unit\Inventory;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Inventory\Application\UseCases\CreateProductVariantUseCase;
use Modules\Inventory\Application\UseCases\UpdateProductVariantUseCase;
use Modules\Inventory\Domain\Contracts\ProductRepositoryInterface;
use Modules\Inventory\Domain\Contracts\ProductVariantRepositoryInterface;
use Modules\Inventory\Domain\Events\ProductVariantCreated;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Product Variants use cases.
 *
 * Covers:
 *  - CreateProductVariantUseCase: missing product_id guard, missing sku guard,
 *    missing name guard, product not found guard, duplicate sku guard,
 *    successful creation with BCMath normalisation + event dispatch.
 *  - UpdateProductVariantUseCase: not-found guard, successful update with
 *    BCMath normalisation.
 */
class ProductVariantUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // CreateProductVariantUseCase
    // -------------------------------------------------------------------------

    public function test_create_throws_when_product_id_missing(): void
    {
        $productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $variantRepo = Mockery::mock(ProductVariantRepositoryInterface::class);

        $useCase = new CreateProductVariantUseCase($productRepo, $variantRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Product is required for a variant.');
        $useCase->execute(['product_id' => '', 'sku' => 'V-001', 'name' => 'Red / L', 'tenant_id' => 't1']);
    }

    public function test_create_throws_when_sku_missing(): void
    {
        $productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $variantRepo = Mockery::mock(ProductVariantRepositoryInterface::class);

        $useCase = new CreateProductVariantUseCase($productRepo, $variantRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('SKU is required for a variant.');
        $useCase->execute(['product_id' => 'prod-1', 'sku' => '', 'name' => 'Red / L', 'tenant_id' => 't1']);
    }

    public function test_create_throws_when_name_missing(): void
    {
        $productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $variantRepo = Mockery::mock(ProductVariantRepositoryInterface::class);

        $useCase = new CreateProductVariantUseCase($productRepo, $variantRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Name is required for a variant.');
        $useCase->execute(['product_id' => 'prod-1', 'sku' => 'V-001', 'name' => '', 'tenant_id' => 't1']);
    }

    public function test_create_throws_when_product_not_found(): void
    {
        $productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $productRepo->shouldReceive('findById')->with('prod-missing')->andReturn(null);

        $variantRepo = Mockery::mock(ProductVariantRepositoryInterface::class);

        $useCase = new CreateProductVariantUseCase($productRepo, $variantRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Product not found.');
        $useCase->execute(['product_id' => 'prod-missing', 'sku' => 'V-001', 'name' => 'Red / L', 'tenant_id' => 't1']);
    }

    public function test_create_throws_when_sku_duplicate(): void
    {
        $product = (object) ['id' => 'prod-1'];

        $productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $productRepo->shouldReceive('findById')->with('prod-1')->andReturn($product);

        $variantRepo = Mockery::mock(ProductVariantRepositoryInterface::class);
        $variantRepo->shouldReceive('findBySku')->with('t1', 'V-001')->andReturn((object) ['id' => 'existing-variant']);

        $useCase = new CreateProductVariantUseCase($productRepo, $variantRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('A variant with this SKU already exists.');
        $useCase->execute(['product_id' => 'prod-1', 'sku' => 'V-001', 'name' => 'Red / L', 'tenant_id' => 't1']);
    }

    public function test_create_succeeds_with_bcmath_normalisation_and_dispatches_event(): void
    {
        $product = (object) ['id' => 'prod-1'];

        $created = (object) [
            'id'         => 'variant-uuid-1',
            'tenant_id'  => 't1',
            'product_id' => 'prod-1',
            'sku'        => 'V-001',
            'name'       => 'Red / L',
            'unit_price' => '19.99000000',
            'cost_price' => '10.00000000',
            'is_active'  => true,
        ];

        $productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $productRepo->shouldReceive('findById')->with('prod-1')->andReturn($product);

        $variantRepo = Mockery::mock(ProductVariantRepositoryInterface::class);
        $variantRepo->shouldReceive('findBySku')->with('t1', 'V-001')->andReturn(null);
        $variantRepo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($d) =>
                $d['product_id'] === 'prod-1' &&
                $d['sku'] === 'V-001' &&
                $d['unit_price'] === '19.99000000' &&
                $d['cost_price'] === '10.00000000' &&
                $d['is_active'] === true
            ))
            ->andReturn($created);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn ($cb) => $cb());

        Event::shouldReceive('dispatch')
            ->once()
            ->with(Mockery::type(ProductVariantCreated::class));

        $useCase = new CreateProductVariantUseCase($productRepo, $variantRepo);
        $result = $useCase->execute([
            'product_id' => 'prod-1',
            'sku'        => 'V-001',
            'name'       => 'Red / L',
            'tenant_id'  => 't1',
            'unit_price' => '19.99',
            'cost_price' => '10',
        ]);

        $this->assertSame('variant-uuid-1', $result->id);
        $this->assertSame('19.99000000', $result->unit_price);
    }

    // -------------------------------------------------------------------------
    // UpdateProductVariantUseCase
    // -------------------------------------------------------------------------

    public function test_update_throws_when_variant_not_found(): void
    {
        $variantRepo = Mockery::mock(ProductVariantRepositoryInterface::class);
        $variantRepo->shouldReceive('findById')->with('missing-id')->andReturn(null);

        $useCase = new UpdateProductVariantUseCase($variantRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Product variant not found.');
        $useCase->execute('missing-id', ['name' => 'Blue / M']);
    }

    public function test_update_succeeds_and_normalises_prices(): void
    {
        $existing = (object) ['id' => 'variant-1', 'name' => 'Red / L', 'unit_price' => '10.00'];
        $updated  = (object) ['id' => 'variant-1', 'name' => 'Blue / M', 'unit_price' => '25.50000000'];

        $variantRepo = Mockery::mock(ProductVariantRepositoryInterface::class);
        $variantRepo->shouldReceive('findById')->with('variant-1')->andReturn($existing);
        $variantRepo->shouldReceive('update')
            ->once()
            ->with('variant-1', Mockery::on(fn ($d) =>
                $d['name'] === 'Blue / M' &&
                $d['unit_price'] === '25.50000000'
            ))
            ->andReturn($updated);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn ($cb) => $cb());

        $useCase = new UpdateProductVariantUseCase($variantRepo);
        $result = $useCase->execute('variant-1', ['name' => 'Blue / M', 'unit_price' => '25.5']);

        $this->assertSame('Blue / M', $result->name);
        $this->assertSame('25.50000000', $result->unit_price);
    }
}
