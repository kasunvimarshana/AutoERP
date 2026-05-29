<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use Modules\Pricing\Application\Services\DiscountService;
use PHPUnit\Framework\TestCase;

final class DiscountServiceTest extends TestCase
{
    public function test_discount_amount_is_capped_at_base_amount(): void
    {
        $service = new DiscountService;

        $result = $service->resolveDiscounts([
            [
                'discount_type' => 'fixed',
                'discount_value' => 120,
                'priority' => 10,
                'is_stackable' => true,
            ],
        ], 100.0, 1.0);

        self::assertTrue($result->isSuccess());

        $preview = $result->valueOrFail();

        self::assertSame(100.0, $preview['discount_amount']);
        self::assertSame(100.0, $preview['discount_percentage']);
    }
}
