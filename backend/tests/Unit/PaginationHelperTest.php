<?php
namespace Tests\Unit;

use App\Helpers\PaginationHelper;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Tests\TestCase;

class PaginationHelperTest extends TestCase
{
    public function test_returns_all_when_no_per_page(): void
    {
        $items = collect([1, 2, 3, 4, 5]);
        $result = PaginationHelper::paginate($items, null);
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(5, $result);
    }

    public function test_returns_paginated_when_per_page_given(): void
    {
        $items = collect(range(1, 20));
        $result = PaginationHelper::paginate($items, 5, 1);
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(20, $result->total());
        $this->assertCount(5, $result->items());
    }

    public function test_paginates_arrays(): void
    {
        $items = range(1, 10);
        $result = PaginationHelper::paginate($items, 3, 2);
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(10, $result->total());
        $this->assertCount(3, $result->items());
    }
}
