<?php
declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Modules\Pricing\Application\Services\ResolvePriceService;
use Modules\Pricing\Domain\Entities\PriceList;
use Modules\Pricing\Domain\Entities\PriceListItem;
use Modules\Pricing\Domain\Entities\TaxRate;
use Modules\Pricing\Domain\RepositoryInterfaces\PriceListItemRepositoryInterface;

class PricingModuleTest extends TestCase
{
    // ──────────────────────────────────────────────────────────────────────
    // PriceList entity tests
    // ──────────────────────────────────────────────────────────────────────

    private function makePriceList(): PriceList
    {
        return new PriceList(1, 1, 'Retail', 'USD', 10.0, true, true, '2024-01-01', '2024-12-31', null, null);
    }

    public function test_price_list_creation(): void
    {
        $pl = $this->makePriceList();
        $this->assertEquals('Retail', $pl->getName());
        $this->assertEquals('USD', $pl->getCurrency());
        $this->assertEquals(10.0, $pl->getDiscountPercent());
        $this->assertTrue($pl->isDefault());
        $this->assertTrue($pl->isActive());
    }

    // ──────────────────────────────────────────────────────────────────────
    // TaxRate entity tests
    // ──────────────────────────────────────────────────────────────────────

    private function makeTaxRate(float $rate = 10.0): TaxRate
    {
        return new TaxRate(1, 1, 'GST', 'GST10', $rate, 'inclusive', false, true, 'all', null, null);
    }

    public function test_tax_rate_creation(): void
    {
        $t = $this->makeTaxRate();
        $this->assertEquals('GST', $t->getName());
        $this->assertEquals('GST10', $t->getCode());
        $this->assertEquals(10.0, $t->getRate());
        $this->assertTrue($t->isActive());
        $this->assertFalse($t->isCompound());
    }

    public function test_tax_rate_calculation(): void
    {
        $t = $this->makeTaxRate(15.0);
        $this->assertEqualsWithDelta(15.0, $t->calculate(100.0), 0.001);
    }

    public function test_tax_rate_compound(): void
    {
        $t = new TaxRate(2, 1, 'Compound Tax', 'CT', 5.0, 'exclusive', true, true, 'all', null, null);
        $this->assertTrue($t->isCompound());
    }

    // ──────────────────────────────────────────────────────────────────────
    // PriceListItem entity tests
    // ──────────────────────────────────────────────────────────────────────

    private function makeItem(string $type = PriceListItem::TYPE_FIXED, float $value = 25.0, float $minQty = 1.0): PriceListItem
    {
        return new PriceListItem(1, 1, 1, 1, null, $type, $value, $minQty, 'USD', null, null);
    }

    public function test_price_list_item_fixed_price(): void
    {
        $item = $this->makeItem(PriceListItem::TYPE_FIXED, 25.0);
        $this->assertEquals(25.0, $item->computePrice(30.0, 5.0));  // ignores base price
    }

    public function test_price_list_item_percentage_discount(): void
    {
        $item = $this->makeItem(PriceListItem::TYPE_PERCENTAGE, 20.0);  // 20% off
        $this->assertEqualsWithDelta(80.0, $item->computePrice(100.0, 5.0), 0.001);
    }

    public function test_price_list_item_min_quantity_not_met_returns_base(): void
    {
        $item = $this->makeItem(PriceListItem::TYPE_FIXED, 20.0, 10.0); // requires qty >= 10
        $this->assertEquals(30.0, $item->computePrice(30.0, 5.0));      // qty=5 < minQty=10
    }

    public function test_price_list_item_min_quantity_met_applies(): void
    {
        $item = $this->makeItem(PriceListItem::TYPE_FIXED, 20.0, 10.0); // requires qty >= 10
        $this->assertEquals(20.0, $item->computePrice(30.0, 10.0));     // qty=10 = minQty
    }

    public function test_price_list_item_constants(): void
    {
        $this->assertEquals('fixed',      PriceListItem::TYPE_FIXED);
        $this->assertEquals('percentage', PriceListItem::TYPE_PERCENTAGE);
    }

    // ──────────────────────────────────────────────────────────────────────
    // ResolvePriceService tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_resolve_price_returns_base_when_no_items(): void
    {
        $repo = $this->createMock(PriceListItemRepositoryInterface::class);
        $repo->method('findForProduct')->willReturn([]);

        $service = new ResolvePriceService($repo);
        $price   = $service->resolve(1, 1, 1, 100.0, 5.0);

        $this->assertEquals(100.0, $price);
    }

    public function test_resolve_price_selects_highest_applicable_tier(): void
    {
        $tier1 = new PriceListItem(1, 1, 1, 1, null, PriceListItem::TYPE_FIXED, 90.0, 1.0, 'USD', null, null);
        $tier2 = new PriceListItem(2, 1, 1, 1, null, PriceListItem::TYPE_FIXED, 80.0, 10.0, 'USD', null, null);
        $tier3 = new PriceListItem(3, 1, 1, 1, null, PriceListItem::TYPE_FIXED, 70.0, 50.0, 'USD', null, null);

        $repo = $this->createMock(PriceListItemRepositoryInterface::class);
        $repo->method('findForProduct')->willReturn([$tier1, $tier2, $tier3]);

        $service = new ResolvePriceService($repo);

        // qty=15: tier1 (min=1) ✓, tier2 (min=10) ✓, tier3 (min=50) ✗ → use tier2 price = 80
        $price = $service->resolve(1, 1, 1, 100.0, 15.0);
        $this->assertEquals(80.0, $price);
    }

    public function test_resolve_price_percentage_tier(): void
    {
        $item = new PriceListItem(1, 1, 1, 1, null, PriceListItem::TYPE_PERCENTAGE, 10.0, 1.0, 'USD', null, null);

        $repo = $this->createMock(PriceListItemRepositoryInterface::class);
        $repo->method('findForProduct')->willReturn([$item]);

        $service = new ResolvePriceService($repo);
        $price   = $service->resolve(1, 1, 1, 100.0, 5.0);

        $this->assertEqualsWithDelta(90.0, $price, 0.001); // 10% off 100
    }

    // ──────────────────────────────────────────────────────────────────────
    // PriceList entity – additional tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_price_list_inactive(): void
    {
        $pl = new PriceList(2, 1, 'Wholesale', 'EUR', 0.0, false, false, null, null, null, null);
        $this->assertFalse($pl->isDefault());
        $this->assertFalse($pl->isActive());
        $this->assertEquals('EUR', $pl->getCurrency());
        $this->assertEquals(0.0, $pl->getDiscountPercent());
    }

    public function test_price_list_date_range(): void
    {
        $pl = $this->makePriceList();
        $this->assertEquals('2024-01-01', $pl->getValidFrom());
        $this->assertEquals('2024-12-31', $pl->getValidTo());
    }

    // ──────────────────────────────────────────────────────────────────────
    // TaxRate entity – additional tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_tax_rate_exclusive_type(): void
    {
        $t = new TaxRate(3, 1, 'VAT', 'VAT20', 20.0, 'exclusive', false, true, 'goods', null, null);
        $this->assertEquals('exclusive', $t->getType());
        $this->assertEquals('goods', $t->getAppliesTo());
    }

    public function test_tax_rate_zero_calculate(): void
    {
        $t = $this->makeTaxRate(0.0);
        $this->assertEquals(0.0, $t->calculate(100.0));
    }

    public function test_tax_rate_not_active(): void
    {
        $t = new TaxRate(4, 1, 'Old Tax', 'OT', 5.0, 'inclusive', false, false, 'all', null, null);
        $this->assertFalse($t->isActive());
    }

    // ──────────────────────────────────────────────────────────────────────
    // ResolvePriceService – edge cases
    // ──────────────────────────────────────────────────────────────────────

    public function test_resolve_price_with_only_inapplicable_tiers(): void
    {
        // Both tiers require qty >= 100, but we only have qty=5
        $tier1 = new PriceListItem(1, 1, 1, 1, null, PriceListItem::TYPE_FIXED, 50.0, 100.0, 'USD', null, null);
        $tier2 = new PriceListItem(2, 1, 1, 1, null, PriceListItem::TYPE_FIXED, 40.0, 200.0, 'USD', null, null);

        $repo = $this->createMock(PriceListItemRepositoryInterface::class);
        $repo->method('findForProduct')->willReturn([$tier1, $tier2]);

        $service = new ResolvePriceService($repo);
        $price   = $service->resolve(1, 1, 1, 100.0, 5.0);  // no tier applies

        $this->assertEquals(100.0, $price);  // falls back to base price
    }

    public function test_resolve_price_exact_minimum_quantity(): void
    {
        $item = new PriceListItem(1, 1, 1, 1, null, PriceListItem::TYPE_FIXED, 75.0, 10.0, 'USD', null, null);

        $repo = $this->createMock(PriceListItemRepositoryInterface::class);
        $repo->method('findForProduct')->willReturn([$item]);

        $service = new ResolvePriceService($repo);
        $price   = $service->resolve(1, 1, 1, 100.0, 10.0);  // exactly at minQty

        $this->assertEquals(75.0, $price);
    }
}
