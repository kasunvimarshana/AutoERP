<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

final class VehicleRentalRemovalContractTest extends TestCase
{
    private const PREFLIGHT_MIGRATION = 'database/migrations/2026_07_18_235900_preflight_vehicle_rental_removal.php';

    /** @var array<string, string> */
    private const DROP_MIGRATIONS = [
        'rental_usage_facts' => 'database/migrations/2026_07_18_235901_drop_rental_usage_facts_table.php',
        'rental_calculation_sources' => 'database/migrations/2026_07_18_235902_drop_rental_calculation_sources_table.php',
        'rental_status_histories' => 'database/migrations/2026_07_18_235903_drop_rental_status_histories_table.php',
        'rental_deposit_links' => 'database/migrations/2026_07_18_235904_drop_rental_deposit_links_table.php',
        'rental_deposit_requirements' => 'database/migrations/2026_07_18_235905_drop_rental_deposit_requirements_table.php',
        'rental_calculation_lines' => 'database/migrations/2026_07_18_235906_drop_rental_calculation_lines_table.php',
        'rental_calculation_runs' => 'database/migrations/2026_07_18_235907_drop_rental_calculation_runs_table.php',
        'rental_billing_periods' => 'database/migrations/2026_07_18_235908_drop_rental_billing_periods_table.php',
        'rental_expense_allocations' => 'database/migrations/2026_07_18_235909_drop_rental_expense_allocations_table.php',
        'rental_expenses' => 'database/migrations/2026_07_18_235910_drop_rental_expenses_table.php',
        'rental_usage_contexts' => 'database/migrations/2026_07_18_235911_drop_rental_usage_contexts_table.php',
        'rental_usage_events' => 'database/migrations/2026_07_18_235912_drop_rental_usage_events_table.php',
        'rental_usage_logs' => 'database/migrations/2026_07_18_235913_drop_rental_usage_logs_table.php',
        'rental_custody_event_items' => 'database/migrations/2026_07_18_235914_drop_rental_custody_event_items_table.php',
        'rental_custody_events' => 'database/migrations/2026_07_18_235915_drop_rental_custody_events_table.php',
        'rental_vehicle_replacements' => 'database/migrations/2026_07_18_235916_drop_rental_vehicle_replacements_table.php',
        'rental_driver_assignments' => 'database/migrations/2026_07_18_235917_drop_rental_driver_assignments_table.php',
        'rental_vehicle_allocations' => 'database/migrations/2026_07_18_235918_drop_rental_vehicle_allocations_table.php',
        'vehicle_finance_status_histories' => 'database/migrations/2026_07_18_235919_drop_vehicle_finance_status_histories_table.php',
        'vehicle_finance_installments' => 'database/migrations/2026_07_18_235920_drop_vehicle_finance_installments_table.php',
        'vehicle_finance_agreements' => 'database/migrations/2026_07_18_235921_drop_vehicle_finance_agreements_table.php',
        'rental_agreement_rate_components' => 'database/migrations/2026_07_18_235922_drop_rental_agreement_rate_components_table.php',
        'rental_agreement_rate_versions' => 'database/migrations/2026_07_18_235923_drop_rental_agreement_rate_versions_table.php',
        'rental_agreement_terms' => 'database/migrations/2026_07_18_235924_drop_rental_agreement_terms_table.php',
        'rental_agreements' => 'database/migrations/2026_07_18_235925_drop_rental_agreements_table.php',
        'rental_reservations' => 'database/migrations/2026_07_18_235926_drop_rental_reservations_table.php',
    ];

    /** @var list<string> */
    private const RETIRED_PATHS = [
        'app/Modules/VehicleRental',
        'resources/js/modules/vehicle-rental',
        'tests/Feature/VehicleRental',
        'tests/Unit/VehicleRental',
        'config/vehicle_rental.php',
        'app/Modules/Reporting/Services/VehicleRentalReportDefinitionService.php',
        'tests/Unit/Reporting/VehicleRentalReportDefinitionServiceTest.php',
        'tests/Feature/VehicleService/VehicleServiceRentalAvailabilityIntegrationTest.php',
        'resources/js/app/navigation/tenantWorkspaceNavigation.test.ts',
        'database/migrations/2026_07_18_235959_remove_vehicle_rental_module.php',
    ];

    /** @var list<string> */
    private const ACTIVE_RUNTIME_FILES = [
        'bootstrap/providers.php',
        'routes/console.php',
        'app/Modules/Core/Tenancy/TenantFeature.php',
        'app/Modules/Tenant/Services/Plans/TenantPlanSchema.php',
        'app/Modules/Tenant/Services/TenantEntitlementService.php',
        'app/Modules/Tenant/Http/Resources/TenantPlanResource.php',
        'app/Modules/Item/Services/ItemUsageModuleCatalogue.php',
        'app/Modules/Finance/Database/Seeders/FinanceSeeder.php',
        'app/Modules/Reporting/Services/ReportCatalog.php',
        'app/Modules/Reporting/Services/ReportDefinitionRegistry.php',
        'resources/js/app/router.tsx',
        'resources/js/app/navigation/navigationConfig.ts',
        'resources/js/app/access/tenantModules.ts',
        'resources/js/app/access/financeRouteEntitlements.ts',
        'resources/js/shared/api/endpoints.ts',
        'resources/js/modules/invoice/pages/InvoiceListPage.tsx',
        'resources/js/modules/invoice/pages/InvoiceDetailPage.tsx',
        'resources/js/modules/payment/pages/PaymentListPage.tsx',
    ];

    public function test_vehicle_rental_implementation_paths_are_absent(): void
    {
        foreach (self::RETIRED_PATHS as $relativePath) {
            self::assertFalse(
                file_exists($this->projectPath($relativePath)),
                "Retired Vehicle Rental path [{$relativePath}] must not be restored.",
            );
        }
    }

    public function test_active_runtime_surfaces_do_not_expose_vehicle_rental(): void
    {
        foreach (self::ACTIVE_RUNTIME_FILES as $relativePath) {
            $source = $this->source($relativePath);

            self::assertStringNotContainsString('Modules\\VehicleRental', $source, $relativePath);
            self::assertStringNotContainsString('/vehicle-rental', $source, $relativePath);
            self::assertStringNotContainsString("'vehicle-rental'", $source, $relativePath);
            self::assertStringNotContainsString('"vehicle-rental"', $source, $relativePath);
        }
    }

    public function test_new_rental_payments_are_blocked_while_historical_enum_values_remain_readable(): void
    {
        $validation = $this->source('app/Modules/Payment/Validators/PaymentValidationService.php');
        $paymentType = $this->source('app/Modules/Payment/Enums/PaymentType.php');
        $sourceType = $this->source('app/Modules/Payment/Enums/PaymentSourceType.php');

        self::assertStringContainsString('PaymentType::RentalReceipt', $validation);
        self::assertStringContainsString('PaymentSourceType::RentalDepositRequirement', $validation);
        self::assertStringContainsString('Vehicle Rental payments can no longer be created', $validation);
        self::assertStringContainsString("case RentalReceipt = 'rental_receipt';", $paymentType);
        self::assertStringContainsString("case RentalDepositRequirement = 'rental_deposit_requirement';", $sourceType);
    }

    public function test_historical_rental_invoices_allow_settlement_but_block_source_dependent_lifecycle_actions(): void
    {
        $sourceTypes = $this->source('app/Modules/Invoice/Constants/RetiredInvoiceSource.php');
        $policy = $this->source('app/Modules/Invoice/Services/RetiredInvoiceSourcePolicy.php');
        $statuses = $this->source('app/Modules/Invoice/Services/InvoiceStatusService.php');
        $reversals = $this->source('app/Modules/Invoice/Services/InvoiceReversalService.php');

        foreach ([
            'rental_calculation_run',
            'rental_calculation_line',
            'vehicle_finance_installment',
            'vehicle_finance_installment_component',
        ] as $sourceType) {
            self::assertStringContainsString("'{$sourceType}'", $sourceTypes);
        }

        self::assertStringContainsString('InvoiceStatus::PartiallyPaid', $policy);
        self::assertStringContainsString('InvoiceStatus::Paid', $policy);
        self::assertStringContainsString('cannot be approved, posted, cancelled, voided, or reversed', $policy);
        self::assertStringContainsString('$this->retiredSources->assertEditable($invoice);', $statuses);
        self::assertStringContainsString('$this->retiredSources->assertTransitionAllowed($invoice, $to);', $statuses);
        self::assertStringContainsString('$this->retiredSources->assertReversalAllowed($invoice);', $reversals);
    }

    public function test_decommission_preflight_checks_every_retired_table_before_any_drop_migration_runs(): void
    {
        $preflight = $this->source(self::PREFLIGHT_MIGRATION);

        self::assertStringNotContainsString('Schema::drop', $preflight);
        self::assertStringContainsString('Posted Invoice, Payment, Tax, and Finance records must not be deleted.', $preflight);

        foreach (array_keys(self::DROP_MIGRATIONS) as $table) {
            self::assertStringContainsString(
                "$this->assertTableEmpty('{$table}');",
                $preflight,
            );
        }
    }

    public function test_each_retired_table_has_one_explicit_guarded_drop_migration(): void
    {
        foreach (self::DROP_MIGRATIONS as $table => $relativePath) {
            $migration = $this->source($relativePath);

            self::assertSame(1, substr_count($migration, "DB::table('{$table}')->exists()"), $relativePath);
            self::assertSame(1, substr_count($migration, "Schema::dropIfExists('{$table}')"), $relativePath);
            self::assertStringContainsString('Vehicle Rental removal is irreversible', $migration);
        }
    }

    private function source(string $relativePath): string
    {
        $path = $this->projectPath($relativePath);
        self::assertFileExists($path, "Expected [{$relativePath}] to exist.");
        $source = file_get_contents($path);
        self::assertNotFalse($source, "Unable to read [{$relativePath}].");

        return $source;
    }

    private function projectPath(string $relativePath): string
    {
        return dirname(__DIR__, 3).'/'.$relativePath;
    }
}
