<?php

declare(strict_types=1);

namespace Modules\Pricing\Tests\Unit\Services;

use Modules\Pricing\Services\RuleBasedPricingEngine;
use PHPUnit\Framework\TestCase;

/**
 * RuleBasedPricingEngineTest
 *
 * Tests for rule-based pricing engine
 */
class RuleBasedPricingEngineTest extends TestCase
{
    protected RuleBasedPricingEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new RuleBasedPricingEngine;
    }

    public function test_get_strategy(): void
    {
        $this->assertEquals('rule_based', $this->engine->getStrategy());
    }

    public function test_calculate_without_rules(): void
    {
        $result = $this->engine->calculate('100.00', '10', []);
        $this->assertEquals('1000.000000', $result);
    }

    public function test_calculate_with_set_price_action(): void
    {
        $context = [
            'rules' => [
                [
                    'condition' => [
                        'field' => 'quantity',
                        'operator' => '>',
                        'value' => '5',
                    ],
                    'action' => [
                        'type' => 'set_price',
                        'value' => '90.00',
                    ],
                ],
            ],
        ];

        $result = $this->engine->calculate('100.00', '10', $context);
        $this->assertEquals('900.000000', $result);
    }

    public function test_calculate_with_percentage_decrease(): void
    {
        $context = [
            'rules' => [
                [
                    'condition' => [
                        'field' => 'quantity',
                        'operator' => '>=',
                        'value' => '10',
                    ],
                    'action' => [
                        'type' => 'percentage_decrease',
                        'value' => '15',
                    ],
                ],
            ],
        ];

        $result = $this->engine->calculate('100.00', '10', $context);
        $this->assertEquals('850.000000', $result);
    }

    public function test_calculate_with_compound_conditions(): void
    {
        $context = [
            'rules' => [
                [
                    'condition' => [
                        'operator' => 'and',
                        'conditions' => [
                            ['field' => 'quantity', 'operator' => '>', 'value' => '5'],
                            ['field' => 'customer_type', 'operator' => '=', 'value' => 'wholesale'],
                        ],
                    ],
                    'action' => [
                        'type' => 'percentage_decrease',
                        'value' => '20',
                    ],
                ],
            ],
            'customer_type' => 'wholesale',
        ];

        $result = $this->engine->calculate('100.00', '10', $context);
        $this->assertEquals('800.000000', $result);
    }

    public function test_validate_with_valid_config(): void
    {
        $config = [
            'rules' => [
                [
                    'condition' => [
                        'field' => 'quantity',
                        'operator' => '>',
                        'value' => '10',
                    ],
                    'action' => [
                        'type' => 'set_price',
                        'value' => '85.00',
                    ],
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
                    'condition' => [],
                ],
            ],
        ];

        $this->assertFalse($this->engine->validate($config));
    }
}
