<?php

namespace Tests\Unit\ECommerce;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\ECommerce\Application\UseCases\ConfirmECommerceOrderUseCase;
use Modules\ECommerce\Application\UseCases\PlaceECommerceOrderUseCase;
use Modules\ECommerce\Domain\Contracts\ECommerceOrderLineRepositoryInterface;
use Modules\ECommerce\Domain\Contracts\ECommerceOrderRepositoryInterface;
use Modules\ECommerce\Domain\Contracts\ProductListingRepositoryInterface;
use Modules\ECommerce\Domain\Events\ECommerceOrderConfirmed;
use Modules\ECommerce\Domain\Events\ECommerceOrderPlaced;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ECommerce use cases.
 *
 * Covers order placement BCMath totals, line calculations, order confirmation
 * lifecycle guards, and domain event dispatch.
 */
class ECommerceUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeOrder(string $status = 'pending'): object
    {
        return (object) [
            'id'            => 'order-uuid-1',
            'tenant_id'     => 'tenant-uuid-1',
            'status'        => $status,
            'subtotal'      => '100.00000000',
            'tax_amount'    => '10.00000000',
            'shipping_cost' => '5.00000000',
            'total'         => '115.00000000',
        ];
    }

    private function makePlaceUseCase(object $order): PlaceECommerceOrderUseCase
    {
        $orderRepo = Mockery::mock(ECommerceOrderRepositoryInterface::class);
        $orderRepo->shouldReceive('create')->andReturn($order);

        $lineRepo = Mockery::mock(ECommerceOrderLineRepositoryInterface::class);
        $lineRepo->shouldReceive('create')->andReturn((object) []);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        DB::shouldReceive('table')->andReturnSelf();
        DB::shouldReceive('where')->andReturnSelf();
        DB::shouldReceive('whereYear')->andReturnSelf();
        DB::shouldReceive('count')->andReturn(0);

        return new PlaceECommerceOrderUseCase($orderRepo, $lineRepo);
    }

    // -------------------------------------------------------------------------
    // PlaceECommerceOrderUseCase
    // -------------------------------------------------------------------------

    public function test_places_order_with_pending_status_and_dispatches_event(): void
    {
        $order   = $this->makeOrder('pending');
        $useCase = $this->makePlaceUseCase($order);

        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof ECommerceOrderPlaced);

        $result = $useCase->execute([
            'tenant_id'      => 'tenant-uuid-1',
            'customer_name'  => 'John Doe',
            'customer_email' => 'john@example.com',
            'lines'          => [
                [
                    'product_name' => 'Widget A',
                    'unit_price'   => '50',
                    'quantity'     => '2',
                    'discount'     => '0',
                    'tax_rate'     => '0',
                ],
            ],
        ]);

        $this->assertSame('pending', $result->status);
    }

    public function test_places_order_with_bcmath_line_totals(): void
    {
        // unit_price=100, qty=2, discount=10, tax_rate=10%
        // gross = 100 * 2 = 200
        // after_disc = 200 - 10 = 190
        // tax = 190 * 10/100 = 19
        // line_total = 190 + 19 = 209

        $expectedLineTotal = '209.00000000';

        $orderRepo = Mockery::mock(ECommerceOrderRepositoryInterface::class);
        $orderRepo->shouldReceive('create')
            ->withArgs(fn ($data) => bccomp($data['subtotal'], $expectedLineTotal, 8) === 0)
            ->andReturn($this->makeOrder('pending'));

        $lineRepo = Mockery::mock(ECommerceOrderLineRepositoryInterface::class);
        $lineRepo->shouldReceive('create')
            ->withArgs(fn ($data) => $data['line_total'] === $expectedLineTotal)
            ->once()
            ->andReturn((object) []);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        DB::shouldReceive('table')->andReturnSelf();
        DB::shouldReceive('where')->andReturnSelf();
        DB::shouldReceive('whereYear')->andReturnSelf();
        DB::shouldReceive('count')->andReturn(0);

        Event::shouldReceive('dispatch')->once();

        $useCase = new PlaceECommerceOrderUseCase($orderRepo, $lineRepo);

        $result = $useCase->execute([
            'tenant_id'      => 'tenant-uuid-1',
            'customer_name'  => 'Jane Doe',
            'customer_email' => 'jane@example.com',
            'lines'          => [
                [
                    'product_name' => 'Widget B',
                    'unit_price'   => '100',
                    'quantity'     => '2',
                    'discount'     => '10',
                    'tax_rate'     => '10',
                ],
            ],
        ]);

        $this->assertNotNull($result);
    }

    // -------------------------------------------------------------------------
    // ConfirmECommerceOrderUseCase
    // -------------------------------------------------------------------------

    public function test_confirm_throws_when_order_not_found(): void
    {
        $repo        = Mockery::mock(ECommerceOrderRepositoryInterface::class);
        $lineRepo    = Mockery::mock(ECommerceOrderLineRepositoryInterface::class);
        $listingRepo = Mockery::mock(ProductListingRepositoryInterface::class);
        $repo->shouldReceive('findById')->with('missing-id')->andReturn(null);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new ConfirmECommerceOrderUseCase($repo, $lineRepo, $listingRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/not found/i');

        $useCase->execute('missing-id');
    }

    public function test_confirm_throws_when_order_not_pending(): void
    {
        $repo        = Mockery::mock(ECommerceOrderRepositoryInterface::class);
        $lineRepo    = Mockery::mock(ECommerceOrderLineRepositoryInterface::class);
        $listingRepo = Mockery::mock(ProductListingRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($this->makeOrder('confirmed'));

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')->never();

        $useCase = new ConfirmECommerceOrderUseCase($repo, $lineRepo, $listingRepo);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/pending/i');

        $useCase->execute('order-uuid-1');
    }

    public function test_confirm_transitions_to_confirmed_and_dispatches_event(): void
    {
        $order     = $this->makeOrder('pending');
        $confirmed = (object) array_merge((array) $order, ['status' => 'confirmed']);

        $repo = Mockery::mock(ECommerceOrderRepositoryInterface::class);
        $repo->shouldReceive('findById')->andReturn($order);
        $repo->shouldReceive('update')
            ->withArgs(fn ($id, $data) => $data['status'] === 'confirmed')
            ->once()
            ->andReturn($confirmed);

        $lineRepo    = Mockery::mock(ECommerceOrderLineRepositoryInterface::class);
        $lineRepo->shouldReceive('findByOrder')->andReturn([]);

        $listingRepo = Mockery::mock(ProductListingRepositoryInterface::class);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());
        Event::shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($event) => $event instanceof ECommerceOrderConfirmed);

        $useCase = new ConfirmECommerceOrderUseCase($repo, $lineRepo, $listingRepo);
        $result  = $useCase->execute('order-uuid-1');

        $this->assertSame('confirmed', $result->status);
    }
}
