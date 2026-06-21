<?php

declare(strict_types=1);

namespace Tests\Unit\VehicleRental;

use PHPUnit\Framework\TestCase;

final class VehicleRentalModuleBaselineTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = dirname(__DIR__, 3);
    }

    public function test_vehicle_rental_uses_the_clean_video_based_schema(): void
    {
        $migrationDirectory = $this->root.'/app/Modules/VehicleRental/Database/Migrations';
        $migrations = glob($migrationDirectory.'/*.php') ?: [];
        sort($migrations);

        self::assertCount(24, $migrations);

        $source = implode("\n", array_map(
            static fn (string $path): string => (string) file_get_contents($path),
            $migrations,
        ));

        foreach ($this->requiredTables() as $table) {
            self::assertStringContainsString("Schema::create('{$table}'", $source, "Missing table {$table}");
        }

        foreach ($this->removedLegacyTables() as $table) {
            self::assertStringNotContainsString("Schema::create('{$table}'", $source, "Legacy table {$table} must stay removed");
        }

        self::assertStringContainsString("'triple_overtime_minutes'", $source);
        self::assertStringContainsString("'rental_custody_events'", $source);
        self::assertStringContainsString("'rental_vehicle_replacements'", $source);
        self::assertStringContainsString("'vehicle_finance_installments'", $source);
        self::assertStringContainsString("'rental_deposit_requirements'", $source);
    }

    public function test_core_financial_modules_remain_authoritative(): void
    {
        $moduleSource = $this->moduleSource();

        self::assertStringContainsString('InvoiceCreationService', $moduleSource);
        self::assertStringContainsString('PaymentCreationService', $moduleSource);
        self::assertStringContainsString('PaymentAllocationService', $moduleSource);
        self::assertStringContainsString('TaxCalculationService', $moduleSource);
        self::assertStringContainsString('VehicleStatusService', $moduleSource);

        foreach (['rental_invoice_links', 'rental_payment_links', 'rental_charges'] as $legacyTable) {
            self::assertStringNotContainsString($legacyTable, $moduleSource);
        }
    }

    public function test_public_routes_do_not_bypass_custody_lifecycle(): void
    {
        $routes = (string) file_get_contents($this->root.'/app/Modules/VehicleRental/Routes/api.php');

        self::assertStringNotContainsString("allocations/{allocation}/activate", $routes);
        self::assertStringNotContainsString("allocations/{allocation}/close", $routes);
        self::assertStringContainsString("allocations/{allocation}/custody-events", $routes);
        self::assertStringContainsString("allocations/{allocation}/replacement", $routes);
    }

    /** @return list<string> */
    private function requiredTables(): array
    {
        return [
            'rental_reservations',
            'rental_agreements',
            'rental_agreement_terms',
            'rental_agreement_rate_versions',
            'rental_agreement_rate_components',
            'vehicle_finance_agreements',
            'vehicle_finance_installments',
            'vehicle_finance_status_histories',
            'rental_vehicle_allocations',
            'rental_driver_assignments',
            'rental_vehicle_replacements',
            'rental_custody_events',
            'rental_custody_event_items',
            'rental_usage_logs',
            'rental_usage_events',
            'rental_usage_contexts',
            'rental_expenses',
            'rental_expense_allocations',
            'rental_billing_periods',
            'rental_calculation_runs',
            'rental_calculation_lines',
            'rental_deposit_requirements',
            'rental_deposit_links',
            'rental_status_histories',
        ];
    }

    /** @return list<string> */
    private function removedLegacyTables(): array
    {
        return [
            'rental_agreement_vehicles',
            'rental_agreement_rate_snapshots',
            'rental_pickup_inspections',
            'rental_return_inspections',
            'rental_agreement_vehicle_links',
            'rental_charge_runs',
            'rental_charge_calculations',
            'rental_charges',
            'rental_invoice_links',
            'rental_payment_links',
        ];
    }

    private function moduleSource(): string
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $this->root.'/app/Modules/VehicleRental',
                \FilesystemIterator::SKIP_DOTS,
            ),
        );
        $source = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $source[] = (string) file_get_contents($file->getPathname());
            }
        }

        return implode("\n", $source);
    }
}
