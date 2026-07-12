<?php

declare(strict_types=1);

namespace Modules\Purchase\Tests;

use Modules\Purchase\Services\PurchasePricingService;
use Tests\TestCase;

final class PurchasePricingContextHashTest extends TestCase
{
    public function test_price_source_metadata_does_not_invalidate_the_same_manual_pricing_context(): void
    {
        $service = app(PurchasePricingService::class);
        $context = $this->context();

        $initial = $service->contextHash([
            ...$context,
            'price_source' => 'manual',
            'price_source_id' => null,
            'effective_date' => '2026-06-16',
        ]);
        $afterPurchase = $service->contextHash([
            ...$context,
            'price_source' => 'last_purchase_price',
            'price_source_id' => 42,
            'effective_date' => '2026-06-16',
        ]);

        self::assertSame($initial, $afterPurchase);
    }

    public function test_commercial_pricing_dimension_changes_invalidate_the_context(): void
    {
        $service = app(PurchasePricingService::class);
        $context = $this->context();
        $original = $service->contextHash($context);

        self::assertNotSame($original, $service->contextHash([
            ...$context,
            'supplier_id' => $context['supplier_id'] + 1,
        ]));
        self::assertNotSame($original, $service->contextHash([
            ...$context,
            'purchase_date' => '2026-06-17',
        ]));
    }

    /** @return array<string, int|string|null> */
    private function context(): array
    {
        return [
            'tenant_id' => 1,
            'organization_unit_id' => 2,
            'supplier_id' => 3,
            'item_id' => 4,
            'item_variant_id' => null,
            'uom_id' => 5,
            'currency_id' => 6,
            'purchase_date' => '2026-06-16',
        ];
    }
}
