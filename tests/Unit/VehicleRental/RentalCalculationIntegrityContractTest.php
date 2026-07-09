<?php

declare(strict_types=1);

namespace Tests\Unit\VehicleRental;

use PHPUnit\Framework\TestCase;

final class RentalCalculationIntegrityContractTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = dirname(__DIR__, 3);
    }

    public function test_calculation_transitions_require_and_return_row_versions(): void
    {
        $request = $this->source('app/Modules/VehicleRental/Http/Requests/RentalTransitionRequest.php');
        $controller = $this->source('app/Modules/VehicleRental/Http/Controllers/RentalCalculationController.php');
        $service = $this->source('app/Modules/VehicleRental/Services/RentalCalculationTransitionService.php');
        $calculateRequest = $this->source('app/Modules/VehicleRental/Http/Requests/CalculateRentalRequest.php');
        $resource = $this->source('app/Modules/VehicleRental/Http/Resources/RentalCalculationRunResource.php');
        $api = $this->source('resources/js/modules/vehicle-rental/vehicleRentalApi.ts');
        $page = $this->source('resources/js/modules/vehicle-rental/pages/RentalBillingPage.tsx');
        $types = $this->source('resources/js/modules/vehicle-rental/vehicleRentalTypes.ts');
        $calculationService = $this->source('app/Modules/VehicleRental/Services/RentalCalculationService.php');
        $invoiceIntegration = $this->source('app/Modules/VehicleRental/Services/RentalInvoiceIntegrationService.php');

        self::assertStringContainsString("'expected_version' => ['required', 'integer', 'min:1']", $request);
        self::assertStringContainsString("'expected_agreement_version' => ['required', 'integer', 'min:1']", $calculateRequest);
        self::assertStringContainsString("'period_end' => ['required', 'date', 'after_or_equal:period_start']", $calculateRequest);
        self::assertStringContainsString('RentalCalculationTransitionService $service', $controller);
        self::assertStringContainsString("(int) \$request->input('expected_version')", $controller);
        self::assertStringContainsString("(int) \$request->input('expected_agreement_version')", $controller);
        self::assertStringContainsString('->lockForUpdate()', $service);
        self::assertStringContainsString("'row_version' => \$expectedVersion + 1", $service);
        self::assertStringContainsString("'row_version' => (int) \$this->row_version", $resource);
        self::assertStringContainsString('expected_version: expectedVersion', $api);
        self::assertStringContainsString('expected_agreement_version: expectedAgreementVersion', $api);
        self::assertStringContainsString('run.id, run.row_version, status', $page);
        self::assertStringContainsString('agreement.id, agreement.row_version', $page);
        self::assertStringContainsString('row_version: number;', $types);
        self::assertStringContainsString('source: {', $types);
        self::assertStringNotContainsString('source_id: number;', $types);
        self::assertStringContainsString('RentalExpenseType::Repair', $calculationService);
        self::assertStringContainsString("'expense_allocation_version' => (int) \$allocation->row_version", $calculationService);
        self::assertStringContainsString("'row_version' => DB::raw('row_version + 1')", $calculationService);
        self::assertStringContainsString('Billing period end cannot be before its start.', $calculationService);
        self::assertStringContainsString('$this->bumpAgreementVersion($agreement, $userId);', $calculationService);
        self::assertStringContainsString("'row_version', 'agreement_number', 'agreement_kind'", $resource);
        self::assertStringContainsString('setAgreement((current) => current === null', $page);
        self::assertStringContainsString('dueDateFromPaymentTerms', $invoiceIntegration);
        self::assertStringContainsString('payment_term_days', $invoiceIntegration);
        self::assertStringNotContainsString('due_date: documentDate', $page);
    }

    public function test_schema_enforces_calculation_source_and_usage_fact_identity(): void
    {
        $sources = $this->source('app/Modules/VehicleRental/Database/Migrations/2026_06_12_200025_create_rental_calculation_sources_table.php');
        $contexts = $this->source('app/Modules/VehicleRental/Database/Migrations/2026_06_12_200016_create_rental_usage_contexts_table.php');
        $facts = $this->source('app/Modules/VehicleRental/Database/Migrations/2026_06_12_200026_create_rental_usage_facts_table.php');
        $sourceService = $this->source('app/Modules/VehicleRental/Services/RentalCalculationSourceService.php');

        self::assertStringContainsString("'source_kind' => RentalCalculationSourceKind::UsageContext->value", $sourceService);
        self::assertStringContainsString("'usage_context_id' => \$context->getKey()", $sourceService);
        self::assertStringContainsString("'expense_allocation_id' => null", $sourceService);
        self::assertStringContainsString("'source_kind' => RentalCalculationSourceKind::ExpenseAllocation->value", $sourceService);
        self::assertStringContainsString("'usage_context_id' => null", $sourceService);
        self::assertStringContainsString("'expense_allocation_id' => \$expenseAllocationId", $sourceService);
        self::assertStringContainsString("'expense_allocation_version' => (int) \$allocation->row_version", $sourceService);
        self::assertStringContainsString('RentalUsageFactStatus::Approved', $sourceService);
        self::assertStringContainsString('context_fingerprint', $sourceService);
        self::assertStringContainsString('One or more expense allocations changed after this calculation was prepared.', $sourceService);
        self::assertStringContainsString(
            "['id', 'tenant_id', 'financial_side', 'usage_log_id']",
            $contexts,
        );
        self::assertStringContainsString(
            "['usage_context_id', 'tenant_id', 'financial_side', 'usage_log_id']",
            $facts,
        );
    }

    private function source(string $path): string
    {
        $contents = file_get_contents($this->root.'/'.$path);
        self::assertNotFalse($contents, "Unable to read {$path}");

        return $contents;
    }
}
