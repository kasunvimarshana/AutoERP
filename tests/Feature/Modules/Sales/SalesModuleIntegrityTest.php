<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Sales;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

final class SalesModuleIntegrityTest extends TestCase
{
    public function test_sales_module_does_not_reference_legacy_invoice_module(): void
    {
        $salesRoot = base_path('app/Modules/Sales');
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($salesRoot));

        $patterns = [
            '/\\binvoice_id\\b/i',
            '/original_invoice_id/i',
            "/constrained\\('invoices'\\)/i",
            "/references\\('invoices'\\)/i",
            '/Modules\\\\Invoice/i',
            '/\\bInvoice::class\\b/i',
        ];

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $path = $file->getPathname();
            $content = (string) file_get_contents($path);

            foreach ($patterns as $pattern) {
                self::assertSame(
                    0,
                    preg_match($pattern, $content),
                    sprintf('Legacy invoice reference found in %s for pattern %s', $path, $pattern),
                );
            }
        }
    }

    public function test_sales_migrations_contain_lifecycle_quantity_contracts(): void
    {
        $migrationsRoot =
            'app/Modules/Sales/Infrastructure/Persistence/Eloquent/Migrations/';

        $salesOrdersMigration = (string) file_get_contents(
            base_path($migrationsRoot.'2026_05_06_000001_create_sales_orders_table.php')
        );
        $salesOrderLinesMigration = (string) file_get_contents(
            base_path($migrationsRoot.'2026_05_06_000002_create_sales_order_lines_table.php')
        );
        $gdnHeadersMigration = (string) file_get_contents(
            base_path($migrationsRoot.'2026_05_06_000003_create_gdn_headers_table.php')
        );
        $gdnLinesMigration = (string) file_get_contents(
            base_path($migrationsRoot.'2026_05_06_000004_create_gdn_lines_table.php')
        );
        $salesReturnsMigration = (string) file_get_contents(
            base_path($migrationsRoot.'2026_05_06_000005_create_sales_returns_table.php')
        );
        $salesReturnLinesMigration = (string) file_get_contents(
            base_path($migrationsRoot.'2026_05_06_000006_create_sales_return_lines_table.php')
        );

        self::assertStringContainsString('ordered_qty_total', $salesOrdersMigration);
        self::assertStringContainsString('outstanding_qty_total', $salesOrdersMigration);
        self::assertStringContainsString('reservation_status', $salesOrdersMigration);

        self::assertStringContainsString('ordered_base_qty', $salesOrderLinesMigration);
        self::assertStringContainsString('reserved_qty', $salesOrderLinesMigration);
        self::assertStringContainsString('picked_qty', $salesOrderLinesMigration);
        self::assertStringContainsString('outstanding_qty', $salesOrderLinesMigration);

        self::assertStringContainsString('picking_status', $gdnHeadersMigration);
        self::assertStringContainsString('delivery_status', $gdnHeadersMigration);
        self::assertStringContainsString('expected_qty_total', $gdnHeadersMigration);

        self::assertStringContainsString('expected_qty', $gdnLinesMigration);
        self::assertStringContainsString('delivered_base_qty', $gdnLinesMigration);
        self::assertStringContainsString('short_qty', $gdnLinesMigration);

        self::assertStringContainsString('refund_status', $salesReturnsMigration);
        self::assertStringContainsString('return_qty_total', $salesReturnsMigration);

        self::assertStringContainsString('return_base_qty', $salesReturnLinesMigration);
        self::assertStringContainsString('restock_qty', $salesReturnLinesMigration);
        self::assertStringContainsString('scrap_qty', $salesReturnLinesMigration);
    }
}
