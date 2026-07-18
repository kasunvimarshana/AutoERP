<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

final class VehicleRentalRemovalContractTest extends TestCase
{
    private const REMOVAL_MIGRATION = 'database/migrations/2026_07_18_235959_remove_vehicle_rental_module.php';

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

    public function test_decommission_migration_guards_data_before_dropping_every_rental_table(): void
    {
        $migration = $this->source(self::REMOVAL_MIGRATION);

        self::assertStringContainsString('DB::table($table)->exists()', $migration);
        self::assertStringContainsString('Schema::dropIfExists($table)', $migration);
        self::assertStringContainsString('Vehicle Rental removal is irreversible', $migration);

        foreach (self::RETIRED_TABLES as $table) {
            self::assertStringContainsString("'{$table}'", $migration, "Missing retired table [{$table}].");
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
