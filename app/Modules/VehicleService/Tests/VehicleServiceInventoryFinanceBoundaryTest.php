<?php

declare(strict_types=1);

namespace Modules\VehicleService\Tests;

use PHPUnit\Framework\TestCase;

final class VehicleServiceInventoryFinanceBoundaryTest extends TestCase
{
    public function test_parts_consumption_posts_authoritative_movement_cost_through_semantic_finance_roles(): void
    {
        $finance = $this->source('../Services/VehicleServiceInventoryFinanceService.php');

        self::assertStringContainsString('(string) $movement->total_cost', $finance);
        self::assertStringContainsString('FinancePostingProfileCode::InventoryIssue->value', $finance);
        self::assertStringContainsString('FinanceAccountRoleCode::CostOfGoodsSold->value', $finance);
        self::assertStringContainsString('FinanceAccountRoleCode::Inventory->value', $finance);
        self::assertStringContainsString('sourceId: (int) $movement->getKey()', $finance);
        self::assertStringContainsString('sourceLineId: (int) $line->getKey()', $finance);
        self::assertStringNotContainsString("'1200'", $finance);
        self::assertStringNotContainsString("'5200'", $finance);
    }

    public function test_inventory_and_finance_posting_share_the_vehicle_service_transaction(): void
    {
        $integration = $this->source('../Services/VehicleServiceInventoryIntegrationService.php');
        $transaction = strpos($integration, 'return DB::transaction(');
        $inventoryIssue = strpos($integration, '$movement = $this->inventory->issue(');
        $financePost = strpos($integration, '$this->finance->postIssue($job, $line, $movement, $postedBy);');
        $lineLink = strpos($integration, '$line->inventory_movement_id = $movement->getKey();');

        self::assertIsInt($transaction);
        self::assertIsInt($inventoryIssue);
        self::assertIsInt($financePost);
        self::assertIsInt($lineLink);
        self::assertLessThan($inventoryIssue, $transaction);
        self::assertLessThan($financePost, $inventoryIssue);
        self::assertLessThan($lineLink, $financePost);
    }

    private function source(string $relativePath): string
    {
        $source = file_get_contents(__DIR__.'/'.$relativePath);
        self::assertIsString($source);

        return $source;
    }
}
