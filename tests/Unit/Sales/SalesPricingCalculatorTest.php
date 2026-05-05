<?php

declare(strict_types=1);

namespace Tests\Unit\Sales;

use Modules\Product\Application\Contracts\UomConversionResolverServiceInterface;
use Modules\Sales\Application\Support\SalesPricingCalculator;
use PHPUnit\Framework\TestCase;

class SalesPricingCalculatorTest extends TestCase
{
    public function test_it_calculates_unit_discount_totals_for_sales_orders(): void
    {
        $calculator = new SalesPricingCalculator;

        $result = $calculator->normalizeOrderPayload([
            'tax_total' => '5.000000',
            'metadata' => ['discount_strategy' => 'unit'],
            'lines' => [
                ['ordered_qty' => '2', 'unit_price' => '100', 'discount_pct' => '10'],
                ['ordered_qty' => '1', 'unit_price' => '50', 'discount_pct' => '0'],
            ],
        ]);

        $this->assertSame('250.000000', $result['subtotal']);
        $this->assertSame('20.000000', $result['discount_total']);
        $this->assertSame('235.000000', $result['grand_total']);
        $this->assertSame('180.000000', $result['lines'][0]['line_total']);
    }

    public function test_it_applies_hybrid_basket_discount_for_invoices(): void
    {
        $calculator = new SalesPricingCalculator;

        $result = $calculator->normalizeInvoicePayload([
            'metadata' => [
                'discount_strategy' => 'hybrid',
                'discount_type' => 'percentage',
                'discount_value' => '5',
                'stack_discounts' => true,
            ],
            'lines' => [
                ['quantity' => '2', 'unit_price' => '100', 'discount_pct' => '10', 'tax_amount' => '18'],
            ],
        ]);

        $this->assertSame('200.000000', $result['subtotal']);
        $this->assertSame('29.000000', $result['discount_total']);
        $this->assertSame('18.000000', $result['tax_total']);
        $this->assertSame('189.000000', $result['grand_total']);
    }

    public function test_it_annotates_lines_with_base_uom_conversion_when_resolver_is_available(): void
    {
        $resolver = new class implements UomConversionResolverServiceInterface {
            public function resolveFactor(int $tenantId, ?int $productId, int $fromUomId, int $toUomId): array
            {
                return ['factor' => '1', 'path' => [$fromUomId, $toUomId]];
            }

            public function convertQuantity(int $tenantId, ?int $productId, int $fromUomId, int $toUomId, string $quantity, int $scale = 6): array
            {
                return [
                    'quantity' => $quantity,
                    'factor' => '1',
                    'path' => [$fromUomId, $toUomId],
                    'from_uom_id' => $fromUomId,
                    'to_uom_id' => $toUomId,
                ];
            }

            public function normalizeToProductBase(int $tenantId, int $productId, int $fromUomId, string $quantity, int $scale = 6): array
            {
                return [
                    'quantity' => '24.000000',
                    'base_uom_id' => 99,
                    'factor' => '12.000000',
                    'path' => [$fromUomId, 99],
                    'from_uom_id' => $fromUomId,
                ];
            }
        };

        $calculator = new SalesPricingCalculator($resolver);

        $result = $calculator->normalizeOrderPayload([
            'tenant_id' => 1,
            'metadata' => ['discount_strategy' => 'unit'],
            'lines' => [
                [
                    'product_id' => 501,
                    'uom_id' => 7,
                    'ordered_qty' => '2',
                    'unit_price' => '5',
                ],
            ],
        ]);

        $this->assertSame(99, $result['lines'][0]['base_uom_id']);
        $this->assertSame('24.000000', $result['lines'][0]['base_quantity']);
        $this->assertSame('12.000000', $result['lines'][0]['uom_conversion_factor']);
    }
}
