<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class InventoryReorderRuleRoutesTest extends TestCase
{
    public function test_reorder_rules_index_requires_authentication(): void
    {
        $this->getJson('/api/inventory/stock-reorder-rules')->assertStatus(401);
    }

    public function test_reorder_rules_store_requires_authentication(): void
    {
        $this->postJson('/api/inventory/stock-reorder-rules', [])->assertStatus(401);
    }

    public function test_reorder_rules_show_requires_authentication(): void
    {
        $this->getJson('/api/inventory/stock-reorder-rules/1')->assertStatus(401);
    }

    public function test_reorder_rules_update_requires_authentication(): void
    {
        $this->putJson('/api/inventory/stock-reorder-rules/1', [])->assertStatus(401);
    }

    public function test_reorder_rules_destroy_requires_authentication(): void
    {
        $this->deleteJson('/api/inventory/stock-reorder-rules/1')->assertStatus(401);
    }

    public function test_reorder_rules_low_stock_requires_authentication(): void
    {
        $this->getJson('/api/inventory/stock-reorder-rules/low-stock')->assertStatus(401);
    }
}
