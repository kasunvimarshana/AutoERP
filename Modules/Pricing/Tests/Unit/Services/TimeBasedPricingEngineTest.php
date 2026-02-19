<?php

declare(strict_types=1);

namespace Modules\Pricing\Tests\Unit\Services;

use Modules\Pricing\Services\TimeBasedPricingEngine;
use PHPUnit\Framework\TestCase;

/**
 * TimeBasedPricingEngineTest
 *
 * Tests for time-based pricing engine
 */
class TimeBasedPricingEngineTest extends TestCase
{
    protected TimeBasedPricingEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new TimeBasedPricingEngine;
    }

    public function test_get_strategy(): void
    {
        $this->assertEquals('time_based', $this->engine->getStrategy());
    }

    public function test_calculate_without_rules(): void
    {
        $result = $this->engine->calculate('100.00', '10', []);
        $this->assertEquals('1000.000000', $result);
    }

    public function test_calculate_with_day_of_week_rule(): void
    {
        $context = [
            'rules' => [
                [
                    'day_of_week' => [1, 2, 3, 4, 5],
                    'price' => '90.00',
                ],
            ],
            'date' => '2024-01-15 10:00:00',
        ];

        $result = $this->engine->calculate('100.00', '10', $context);
        $this->assertEquals('900.000000', $result);
    }

    public function test_calculate_with_hour_range_rule(): void
    {
        $context = [
            'rules' => [
                [
                    'hour_start' => 9,
                    'hour_end' => 17,
                    'adjustment_percentage' => '10',
                ],
            ],
            'date' => '2024-01-15 14:00:00',
        ];

        $result = $this->engine->calculate('100.00', '10', $context);
        $this->assertEquals('1100.000000', $result);
    }

    public function test_validate_with_valid_config(): void
    {
        $config = [
            'rules' => [
                [
                    'day_of_week' => [1, 2, 3],
                    'price' => '120.00',
                ],
            ],
        ];

        $this->assertTrue($this->engine->validate($config));
    }

    public function test_validate_with_invalid_config(): void
    {
        $config = [
            'rules' => [
                [
                    'day_of_week' => [1, 2, 3],
                ],
            ],
        ];

        $this->assertFalse($this->engine->validate($config));
    }
}
