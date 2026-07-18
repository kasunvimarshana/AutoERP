<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

final class VehicleRentalRemovalContractTest extends TestCase
{
    private const PREFLIGHT_MIGRATION = 'database/migrations/2026_07_18_235900_preflight_vehicle_rental_removal.php';

    /** @var list<string> */
    private const RETIRED_TABLES = [
        'rental_usage_facts',
        'rental_calculation_sources',
        'rental_status_histories',
        'rental_deposit_links',
        'rental_deposit_requirements',
        'rental_calculation_lines',
        'rental_calculation_runs',
        'rental_billing_periods',
        'rental_expense_allocations',
        'rental_expenses',
        'rental_usage_contexts',
        'rental_usage_events',
        'rental_usage_logs',
        'rental_custody_event_items',
        'rental_custody_events',
        'rental_vehicle_replacements',
        'rental_driver_assignments',
        'rental_vehicle_allocations',
        'vehicle_finance_status_histories',
        'vehicle_finance_installments',
        'vehicle_finance_agreements',
        'rental_agreement_rate_components',
        'rental_agreement_rate_versions',
        'rental_agreement_terms',
        'rental_agreements',
        'rental_reservations',
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

        foreach (self::RETIRED_TABLES as $table) {
            self::assertStringContainsString("'{$table}'", $preflight);
        }
    }

    public function test_each_retired_table_has_one_explicit_guarded_drop_migration(): void
    {
        foreach (self::RETIRED_TABLES as $index => $table) {
            $timestamp = 235901 + $index;
            $relativePath = sprintf(
                'database/migrations/2026_07_18_%06d_drop_%s_table.php',
                $timestamp,
                $table,
            );
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
