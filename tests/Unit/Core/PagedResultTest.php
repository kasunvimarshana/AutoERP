<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use Modules\Core\DTOs\PagedResult;
use PHPUnit\Framework\TestCase;

final class PagedResultTest extends TestCase
{
    public function test_it_uses_integer_page_math_and_reports_bounds(): void
    {
        $result = new PagedResult(['a', 'b'], 12, 2, 5);

        self::assertSame(3, $result->pageCount());
        self::assertTrue($result->hasMore());
        self::assertSame([
            'current_page' => 2,
            'from' => 6,
            'last_page' => 3,
            'per_page' => 5,
            'to' => 10,
            'total' => 12,
        ], $result->paginationMeta());
    }

    public function test_page_beyond_available_data_has_null_bounds(): void
    {
        $result = new PagedResult([], 2, 4, 10);

        self::assertFalse($result->hasMore());
        self::assertNull($result->paginationMeta()['from']);
        self::assertNull($result->paginationMeta()['to']);
    }

    public function test_empty_result_keeps_a_single_display_page(): void
    {
        $result = new PagedResult([], 0, 1, 20);

        self::assertSame(0, $result->pageCount());
        self::assertSame(1, $result->paginationMeta()['last_page']);
    }
}
