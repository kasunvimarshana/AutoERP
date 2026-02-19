<?php

declare(strict_types=1);

namespace Modules\Pricing\Tests\Unit\Services;

use Modules\Pricing\Services\VolumePricingEngine;
use PHPUnit\Framework\TestCase;

/**
 * VolumePricingEngineTest
 *
 * Tests for volume-based pricing engine
 */
class VolumePricingEngineTest extends TestCase
{
    protected VolumePricingEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new VolumePricingEngine;
    }

    public function test_get_strategy(): void
    {
        $this->assertEquals('volume', $this->engine->getStrategy());
    }

    public function test_calculate_without_thresholds(): void
    {
        $result = $this->engine->calculate('100.00', '10', []);
        $this->assertEquals('1000.000000', $result);
    }

    public function test_calculate_with_discount_percentage(): void
    {
        $context = [
            'thresholds' => [
                ['min_quantity' => '10', 'discount_percentage' => '10'],
                ['min_quantity' => '100', 'discount_percentage' => '20'],
            ],
        ];

        $result = $this->engine->calculate('100.00', '50', $context);
        $this->assertEquals('4500.000000', $result);
    }

    public function test_calculate_with_fixed_price(): void
    {
        $context = [
            'thresholds' => [
                ['min_quantity' => '100', 'price' => '85.00'],
            ],
        ];

        $result = $this->engine->calculate('100.00', '150', $context);
        $this->assertEquals('12750.000000', $result);
    }

    public function test_validate_with_valid_config(): void
    {
        $config = [
            'thresholds' => [
                ['min_quantity' => '10', 'discount_percentage' => '5'],
                ['min_quantity' => '100', 'price' => '90.00'],
            ],
        ];

        $this->assertTrue($this->engine->validate($config));
    }

    public function test_validate_with_invalid_config(): void
    {
        $config = [
            'thresholds' => [
                ['min_quantity' => '10'],
            ],
        ];

        $this->assertFalse($this->engine->validate($config));
    }
}
