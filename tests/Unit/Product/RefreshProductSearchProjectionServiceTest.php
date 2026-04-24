<?php

declare(strict_types=1);

namespace Tests\Unit\Product;

use Illuminate\Support\Facades\Cache;
use Modules\Product\Application\Contracts\ProductSearchProjectionRefreshDispatcherInterface;
use Modules\Product\Application\Services\RefreshProductSearchProjectionService;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class RefreshProductSearchProjectionServiceTest extends TestCase
{
    /** @var ProductSearchProjectionRefreshDispatcherInterface&MockObject */
    private ProductSearchProjectionRefreshDispatcherInterface $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->dispatcher = $this->createMock(ProductSearchProjectionRefreshDispatcherInterface::class);
    }

    public function test_execute_dispatches_refresh_job_once_for_same_product_within_debounce_window(): void
    {
        $service = new RefreshProductSearchProjectionService($this->dispatcher);

        $this->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(11, 222, 'product-search-projection:refresh:11:222', 2);

        $first = $service->execute(11, 222);
        $second = $service->execute(11, 222);

        $this->assertSame(1, $first);
        $this->assertSame(0, $second);
    }

    public function test_execute_returns_zero_for_invalid_identifiers(): void
    {
        $service = new RefreshProductSearchProjectionService($this->dispatcher);

        $this->dispatcher->expects($this->never())->method('dispatch');

        $this->assertSame(0, $service->execute(0, 10));
        $this->assertSame(0, $service->execute(10, 0));
    }
}
