<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\VehicleService;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

final class VehicleServiceModuleIntegrityTest extends TestCase
{
    public function testVehicleServiceModuleDoesNotReferenceLegacyInvoiceModuleDirectly(): void
    {
        $moduleRoot = base_path('app/Modules/VehicleService');
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($moduleRoot));

        $patterns = [
            "/constrained\('invoices'\)/i",
            "/references\('invoices'\)/i",
            "/Modules\\\\Invoice/i",
            "/\bInvoice::class\b/i",
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

    public function testVehicleServiceMigrationsContainEnterpriseWorkflowContracts(): void
    {
        $root = 'app/Modules/VehicleService/Infrastructure/Persistence/Eloquent/Migrations/';

        $jobCards = (string) file_get_contents(
            base_path($root . '2026_05_09_000002_create_vehicle_service_job_cards_table.php')
        );
        $jobCardLines = (string) file_get_contents(
            base_path($root . '2026_05_09_000003_create_vehicle_service_job_card_lines_table.php')
        );
        $laborAssignments = (string) file_get_contents(
            base_path($root . '2026_05_09_000006_create_vehicle_service_labor_assignments_table.php')
        );
        $settings = (string) file_get_contents(
            base_path($root . '2026_05_09_000011_create_vehicle_service_settings_table.php')
        );
        $externalServices = (string) file_get_contents(
            base_path($root . '2026_05_09_000012_create_vehicle_service_job_external_services_table.php')
        );
        $customerSupplied = (string) file_get_contents(
            base_path($root . '2026_05_09_000013_create_vehicle_service_job_customer_supplied_items_table.php')
        );
        $statusHistory = (string) file_get_contents(
            base_path($root . '2026_05_09_000014_create_vehicle_service_job_status_histories_table.php')
        );
        $documentLinks = (string) file_get_contents(
            base_path($root . '2026_05_09_000015_create_vehicle_service_job_document_links_table.php')
        );
        $paymentLinks = (string) file_get_contents(
            base_path($root . '2026_05_09_000016_create_vehicle_service_job_payment_links_table.php')
        );
        $inventoryLinks = (string) file_get_contents(
            base_path($root . '2026_05_09_000017_create_vehicle_service_job_inventory_links_table.php')
        );

        self::assertStringContainsString('invoice_status', $jobCards);
        self::assertStringContainsString('payment_status', $jobCards);
        self::assertStringContainsString('finance_status', $jobCards);
        self::assertStringContainsString('advance_amount', $jobCards);
        self::assertStringContainsString('vehicle_ownership_id', $jobCards);
        self::assertStringContainsString('service_customer_type', $jobCards);
        self::assertStringContainsString('billing_customer_type', $jobCards);
        self::assertStringContainsString('payer_type', $jobCards);

        self::assertStringContainsString('line_type', $jobCardLines);
        self::assertStringContainsString('is_customer_supplied', $jobCardLines);
        self::assertStringContainsString('is_external_service', $jobCardLines);
        self::assertStringContainsString('reserved_qty', $jobCardLines);
        self::assertStringContainsString('consumed_qty', $jobCardLines);

        self::assertStringContainsString('split_type', $laborAssignments);
        self::assertStringContainsString('split_amount', $laborAssignments);

        self::assertStringContainsString('auto_invoice_trigger_status', $settings);
        self::assertStringContainsString('service_invoice_document_type_code', $settings);

        self::assertStringContainsString('provider_type', $externalServices);
        self::assertStringContainsString('supplier_id', $externalServices);
        self::assertStringContainsString('line_total', $externalServices);

        self::assertStringContainsString('used_qty', $customerSupplied);
        self::assertStringContainsString('returned_qty', $customerSupplied);

        self::assertStringContainsString('workflow_action', $statusHistory);
        self::assertStringContainsString('document_id', $documentLinks);
        self::assertStringContainsString('allocated_amount', $paymentLinks);
        self::assertStringContainsString('stock_movement_id', $inventoryLinks);
    }
}
