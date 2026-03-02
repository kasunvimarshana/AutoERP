<?php

declare(strict_types=1);

namespace Modules\Pricing\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Pricing\Application\Services\PricingService;
use Modules\Pricing\Domain\Contracts\PricingRepositoryContract;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PricingService list and create operations.
 *
 * The repository is stubbed — no database or Laravel bootstrap required.
 * These tests exercise the delegation logic in listPriceLists().
 */
class PricingServiceListTest extends TestCase
{
    private function makeService(?PricingRepositoryContract $repo = null): PricingService
    {
        return new PricingService(
            $repo ?? $this->createMock(PricingRepositoryContract::class)
        );
    }

    // -------------------------------------------------------------------------
    // listPriceLists — delegation to repository all()
    // -------------------------------------------------------------------------

    public function test_list_price_lists_delegates_to_repository_all(): void
    {
        $expected = new Collection();

        $repo = $this->createMock(PricingRepositoryContract::class);
        $repo->expects($this->once())
            ->method('all')
            ->willReturn($expected);

        $result = $this->makeService($repo)->listPriceLists();

        $this->assertSame($expected, $result);
    }

    public function test_list_price_lists_returns_collection_type(): void
    {
        $repo = $this->createMock(PricingRepositoryContract::class);
        $repo->method('all')->willReturn(new Collection());

        $result = $this->makeService($repo)->listPriceLists();

        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_list_price_lists_returns_empty_collection_when_none_exist(): void
    {
        $repo = $this->createMock(PricingRepositoryContract::class);
        $repo->method('all')->willReturn(new Collection());

        $result = $this->makeService($repo)->listPriceLists();

        $this->assertCount(0, $result);
    }

    public function test_list_price_lists_returns_all_items_from_repository(): void
    {
        $model1 = $this->createMock(Model::class);
        $model2 = $this->createMock(Model::class);

        $repo = $this->createMock(PricingRepositoryContract::class);
        $repo->method('all')->willReturn(new Collection([$model1, $model2]));

        $result = $this->makeService($repo)->listPriceLists();

        $this->assertCount(2, $result);
    }
}
