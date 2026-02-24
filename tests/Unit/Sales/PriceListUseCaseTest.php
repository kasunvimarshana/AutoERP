<?php

namespace Tests\Unit\Sales;

use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Sales\Application\UseCases\AddPriceListItemUseCase;
use Modules\Sales\Application\UseCases\CreatePriceListUseCase;
use Modules\Sales\Application\UseCases\ResolvePriceForProductUseCase;
use Modules\Sales\Domain\Contracts\PriceListRepositoryInterface;
use Modules\Sales\Domain\Events\PriceListCreated;
use Modules\Sales\Domain\Events\PriceListItemAdded;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Sales Price List use cases.
 *
 * Covers: price list creation guards, item-addition guards (amount, percentage cap),
 * and price resolution (flat, percentage-discount, pass-through, tiered selection).
 */
class PriceListUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makePriceList(array $overrides = []): object
    {
        return (object) array_merge([
            'id'            => 'pl-uuid-1',
            'tenant_id'     => 'tenant-uuid-1',
            'name'          => 'Retail',
            'currency_code' => 'USD',
            'is_active'     => true,
            'valid_from'    => null,
            'valid_to'      => null,
            'customer_group' => null,
        ], $overrides);
    }

    private function makePriceListItem(array $overrides = []): object
    {
        return (object) array_merge([
            'id'           => 'pli-uuid-1',
            'price_list_id' => 'pl-uuid-1',
            'product_id'   => 'prod-uuid-1',
            'variant_id'   => null,
            'strategy'     => 'flat',
            'amount'       => '95.00000000',
            'min_qty'      => '1.00000000',
            'uom'          => null,
        ], $overrides);
    }

    // -------------------------------------------------------------------------
    // CreatePriceListUseCase
    // -------------------------------------------------------------------------

    public function test_create_price_list_succeeds_and_dispatches_event(): void
    {
        $repo = Mockery::mock(PriceListRepositoryInterface::class);
        $repo->shouldReceive('create')
            ->once()
            ->andReturn($this->makePriceList());

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof PriceListCreated && $e->priceListId === 'pl-uuid-1');

        $useCase = new CreatePriceListUseCase($repo);
        $result  = $useCase->execute([
            'name'          => 'Retail',
            'currency_code' => 'USD',
        ]);

        $this->assertSame('Retail', $result->name);
    }

    public function test_create_price_list_throws_when_name_empty(): void
    {
        $repo = Mockery::mock(PriceListRepositoryInterface::class);

        $useCase = new CreatePriceListUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/name/i');

        $useCase->execute(['name' => '  ', 'currency_code' => 'USD']);
    }

    public function test_create_price_list_throws_when_currency_code_not_three_chars(): void
    {
        $repo = Mockery::mock(PriceListRepositoryInterface::class);

        $useCase = new CreatePriceListUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/3 characters/i');

        $useCase->execute(['name' => 'Retail', 'currency_code' => 'US']);
    }

    public function test_create_price_list_throws_when_valid_to_before_valid_from(): void
    {
        $repo = Mockery::mock(PriceListRepositoryInterface::class);

        $useCase = new CreatePriceListUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/valid_to must be after/i');

        $useCase->execute([
            'name'          => 'Retail',
            'currency_code' => 'USD',
            'valid_from'    => '2026-06-01',
            'valid_to'      => '2026-01-01',
        ]);
    }

    // -------------------------------------------------------------------------
    // AddPriceListItemUseCase
    // -------------------------------------------------------------------------

    public function test_add_item_succeeds_and_dispatches_event(): void
    {
        $repo = Mockery::mock(PriceListRepositoryInterface::class);
        $repo->shouldReceive('findById')->with('pl-uuid-1')->andReturn($this->makePriceList());
        $repo->shouldReceive('addItem')->once()->andReturn($this->makePriceListItem());

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($e) => $e instanceof PriceListItemAdded
                && $e->priceListId === 'pl-uuid-1'
                && $e->itemId === 'pli-uuid-1');

        $useCase = new AddPriceListItemUseCase($repo);
        $result  = $useCase->execute('pl-uuid-1', [
            'product_id' => 'prod-uuid-1',
            'strategy'   => 'flat',
            'amount'     => '95.00',
            'min_qty'    => '1',
            'tenant_id'  => 'tenant-uuid-1',
        ]);

        $this->assertSame('95.00000000', $result->amount);
    }

    public function test_add_item_throws_when_price_list_not_found(): void
    {
        $repo = Mockery::mock(PriceListRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn(null);

        $useCase = new AddPriceListItemUseCase($repo);

        $this->expectException(ModelNotFoundException::class);

        $useCase->execute('missing-uuid', [
            'product_id' => 'prod-uuid-1',
            'strategy'   => 'flat',
            'amount'     => '50.00',
            'tenant_id'  => 'tenant-uuid-1',
        ]);
    }

    public function test_add_item_throws_when_amount_is_zero(): void
    {
        $repo = Mockery::mock(PriceListRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($this->makePriceList());

        $useCase = new AddPriceListItemUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/greater than zero/i');

        $useCase->execute('pl-uuid-1', [
            'product_id' => 'prod-uuid-1',
            'strategy'   => 'flat',
            'amount'     => '0',
            'tenant_id'  => 'tenant-uuid-1',
        ]);
    }

    public function test_add_item_throws_when_percentage_discount_exceeds_100(): void
    {
        $repo = Mockery::mock(PriceListRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($this->makePriceList());

        $useCase = new AddPriceListItemUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/cannot exceed 100/i');

        $useCase->execute('pl-uuid-1', [
            'product_id' => 'prod-uuid-1',
            'strategy'   => 'percentage_discount',
            'amount'     => '101',
            'tenant_id'  => 'tenant-uuid-1',
        ]);
    }

    public function test_add_item_throws_when_strategy_is_invalid(): void
    {
        $repo = Mockery::mock(PriceListRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($this->makePriceList());

        $useCase = new AddPriceListItemUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/Invalid pricing strategy/i');

        $useCase->execute('pl-uuid-1', [
            'product_id' => 'prod-uuid-1',
            'strategy'   => 'unknown',
            'amount'     => '10',
            'tenant_id'  => 'tenant-uuid-1',
        ]);
    }

    // -------------------------------------------------------------------------
    // ResolvePriceForProductUseCase
    // -------------------------------------------------------------------------

    public function test_resolve_flat_price(): void
    {
        $item = $this->makePriceListItem(['strategy' => 'flat', 'amount' => '90.00000000']);

        $repo = Mockery::mock(PriceListRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($this->makePriceList());
        $repo->shouldReceive('findItem')->andReturn($item);

        $useCase = new ResolvePriceForProductUseCase($repo);
        $result  = $useCase->execute('pl-uuid-1', 'prod-uuid-1', null, '5', '100.00');

        $this->assertSame('90.00000000', $result);
    }

    public function test_resolve_percentage_discount(): void
    {
        $item = $this->makePriceListItem([
            'strategy' => 'percentage_discount',
            'amount'   => '10.00000000',  // 10% off
        ]);

        $repo = Mockery::mock(PriceListRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($this->makePriceList());
        $repo->shouldReceive('findItem')->andReturn($item);

        $useCase  = new ResolvePriceForProductUseCase($repo);
        $resolved = $useCase->execute('pl-uuid-1', 'prod-uuid-1', null, '1', '100.00');

        // 100 - (100 * 10/100) = 90.00000000
        $this->assertSame('90.00000000', $resolved);
    }

    public function test_resolve_returns_base_price_when_no_item_found(): void
    {
        $repo = Mockery::mock(PriceListRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($this->makePriceList());
        $repo->shouldReceive('findItem')->andReturn(null);

        $useCase  = new ResolvePriceForProductUseCase($repo);
        $resolved = $useCase->execute('pl-uuid-1', 'prod-uuid-1', null, '1', '75.50');

        $this->assertSame('75.50000000', $resolved);
    }

    public function test_resolve_throws_when_price_list_not_found(): void
    {
        $repo = Mockery::mock(PriceListRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn(null);

        $useCase = new ResolvePriceForProductUseCase($repo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute('missing-pl', 'prod-uuid-1', null, '1', '50.00');
    }
}
