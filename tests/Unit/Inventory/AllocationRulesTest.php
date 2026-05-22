<?php

declare(strict_types=1);

namespace Tests\Unit\Inventory;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\Inventory\Application\DTOs\AllocationRequest;
use Modules\Inventory\Application\Rules\Allocation\PreferEarliestExpiryRule;
use Modules\Inventory\Application\Rules\Allocation\RequireUnexpiredBatchRule;
use Modules\Inventory\Application\Rules\Allocation\SkipZeroAvailableRule;
use Tests\TestCase;

class AllocationRulesTest extends TestCase
{
    public function testSkipZeroAvailableRuleRemovesNonPositiveRows(): void
    {
        $rule = new SkipZeroAvailableRule();
        $request = new AllocationRequest(tenantId: 1, itemId: 1, requiredQuantity: 1);

        $candidates = new Collection([
            (object) ['stock_level_id' => 1, 'available_quantity' => 0],
            (object) ['stock_level_id' => 2, 'available_quantity' => -1],
            (object) ['stock_level_id' => 3, 'available_quantity' => 4],
        ]);

        $filtered = $rule->apply($candidates, $request);

        $this->assertCount(1, $filtered);
        $this->assertSame(3, $filtered->first()->stock_level_id);
    }

    public function testRequireUnexpiredBatchRuleKeepsUnexpiredAndNullExpiry(): void
    {
        $rule = new RequireUnexpiredBatchRule();
        $request = new AllocationRequest(
            tenantId: 1,
            itemId: 1,
            requiredQuantity: 1,
            ruleContext: ['allow_expired' => false]
        );

        $yesterday = CarbonImmutable::today()->subDay()->toDateString();
        $tomorrow = CarbonImmutable::today()->addDay()->toDateString();

        $candidates = new Collection([
            (object) ['stock_level_id' => 1, 'expiry_date' => $yesterday],
            (object) ['stock_level_id' => 2, 'expiry_date' => $tomorrow],
            (object) ['stock_level_id' => 3, 'expiry_date' => null],
        ]);

        $filtered = $rule->apply($candidates, $request);

        $this->assertCount(2, $filtered);
        $this->assertSame([2, 3], $filtered->pluck('stock_level_id')->all());
    }

    public function testPreferEarliestExpiryRuleOrdersByExpiryThenId(): void
    {
        $rule = new PreferEarliestExpiryRule();
        $request = new AllocationRequest(tenantId: 1, itemId: 1, requiredQuantity: 1);

        $today = CarbonImmutable::today()->toDateString();
        $tomorrow = CarbonImmutable::today()->addDay()->toDateString();

        $candidates = new Collection([
            (object) ['stock_level_id' => 30, 'expiry_date' => null],
            (object) ['stock_level_id' => 20, 'expiry_date' => $tomorrow],
            (object) ['stock_level_id' => 10, 'expiry_date' => $today],
        ]);

        $ordered = $rule->apply($candidates, $request);

        $this->assertSame([10, 20, 30], $ordered->pluck('stock_level_id')->all());
    }
}
