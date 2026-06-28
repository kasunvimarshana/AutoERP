<?php

declare(strict_types=1);

namespace Modules\Item\Tests;

use PHPUnit\Framework\TestCase;

final class ItemPricingArchitectureTest extends TestCase
{
    public function test_item_master_has_no_mutable_standard_price_source(): void
    {
        $migration = $this->source('Database/Migrations/2026_06_12_090003_create_items_table.php');
        $model = $this->source('Models/Item.php');

        self::assertStringNotContainsString("'standard_price'", $migration);
        self::assertStringNotContainsString("'standard_price'", $model);
    }

    public function test_price_routes_expose_create_and_supersede_without_update_or_delete(): void
    {
        $routes = $this->source('Routes/api.php');

        self::assertStringContainsString("Route::post('items/{item}/prices'", $routes);
        self::assertStringContainsString("Route::post('items/{item}/prices/{price}/supersede'", $routes);
        self::assertStringNotContainsString("Route::put('items/{item}/prices/{price}'", $routes);
        self::assertStringNotContainsString("Route::delete('items/{item}/prices/{price}'", $routes);
    }

    public function test_price_revision_model_blocks_mutation_and_deletion(): void
    {
        $model = $this->source('Models/ItemPrice.php');

        self::assertStringContainsString('static::updating', $model);
        self::assertStringContainsString('static::deleting', $model);
        self::assertStringContainsString('Use the supersede command', $model);
        $resource = $this->source('Http/Resources/ItemPriceResource.php');
        self::assertStringNotContainsString("'scope_key' =>", $resource);
        self::assertStringNotContainsString("'lineage_key' =>", $resource);
    }

    public function test_price_schema_preserves_revision_lineage_and_history(): void
    {
        $migration = $this->source('Database/Migrations/2026_06_12_090007_create_item_prices_table.php');

        foreach ([
            "'row_version'",
            "'scope_key'",
            "'lineage_key'",
            "'revision_no'",
            "'supersedes_price_id'",
            "'recorded_from'",
            "'recorded_to'",
            "'correction_reason'",
        ] as $requiredColumn) {
            self::assertStringContainsString($requiredColumn, $migration);
        }

        self::assertStringNotContainsString("'is_active'", $migration);
        self::assertStringNotContainsString('cascadeOnDelete()', $migration);
    }

    public function test_scope_identity_has_one_named_source_of_truth(): void
    {
        self::assertStringContainsString('ItemPriceScopeKey::for', $this->source('Services/ItemPriceService.php'));
        self::assertStringContainsString('ItemPriceScopeKey::for', $this->source('Database/Seeders/ItemSeeder.php'));
    }

    private function source(string $relativePath): string
    {
        $source = file_get_contents(dirname(__DIR__).'/'.$relativePath);
        self::assertNotFalse($source, $relativePath);

        return $source;
    }
}
