<?php

declare(strict_types=1);

namespace Tests\Unit\VehicleRental;

use Modules\Invoice\Enums\InvoiceType;
use Modules\Vehicle\Enums\VehicleStatus;
use Modules\VehicleRental\Enums\VehicleFinanceInstallmentFrequency;
use Modules\VehicleRental\Enums\VehicleFinanceInterestMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RentalArchitectureContractTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = dirname(__DIR__, 3);
    }

    #[Test]
    public function physical_usage_and_commercial_sides_remain_separate(): void
    {
        $usageService = $this->source('app/Modules/VehicleRental/Services/RentalUsageService.php');
        $calculationSources = $this->source('app/Modules/VehicleRental/Services/RentalCalculationSourceService.php');

        self::assertStringContainsString('RentalFinancialSide::Revenue', $usageService);
        self::assertStringContainsString('RentalFinancialSide::Cost', $usageService);
        self::assertStringContainsString('createInitial($revenueContext', $usageService);
        self::assertStringContainsString('createInitial($costContext', $usageService);
        self::assertStringContainsString('commercial usage facts are already consumed', $calculationSources);
    }

    #[Test]
    public function agreement_and_rate_review_use_one_authoritative_contract(): void
    {
        $agreementService = $this->source('app/Modules/VehicleRental/Services/RentalAgreementService.php');
        $rateService = $this->source('app/Modules/VehicleRental/Services/RentalRateVersionService.php');
        $metadataController = $this->source('app/Modules/VehicleRental/Http/Controllers/RentalContextController.php');
        $agreementPage = $this->source('resources/js/modules/vehicle-rental/pages/RentalAgreementCreatePage.tsx');

        self::assertStringContainsString('activateSingleDraftForAgreement', $agreementService);
        self::assertStringContainsString('replaceDraftForAgreement', $rateService);
        self::assertStringContainsString('rate_component_definitions', $metadataController);
        self::assertStringContainsString('componentDefinitions', $agreementPage);
        self::assertStringNotContainsString('eventComponentDefaults', $agreementPage);
        self::assertStringNotContainsString('coreComponentDefaults', $agreementPage);
    }

    #[Test]
    public function vehicle_service_projects_unavailability_through_vehicle_ownership(): void
    {
        $availability = $this->source('app/Modules/VehicleRental/Services/RentalAvailabilityService.php');
        $serviceStatus = $this->source('app/Modules/VehicleService/Services/VehicleServiceStatusService.php');

        self::assertStringContainsString('VehicleStatus::UnderService->value', $availability);
        self::assertStringContainsString('VehicleStatusService', $serviceStatus);
        self::assertStringContainsString('VehicleStatus::UnderService', $serviceStatus);
        self::assertStringContainsString('VehicleStatus::Active', $serviceStatus);
        self::assertStringContainsString("->orderBy('id')", $serviceStatus);
    }

    #[Test]
    public function expense_reversal_cannot_invalidate_an_approved_calculation_source(): void
    {
        $expenseService = $this->source('app/Modules/VehicleRental/Services/RentalExpenseService.php');

        self::assertStringContainsString('RentalCalculationSource', $expenseService);
        self::assertStringContainsString('RentalCalculationSourceStatus::Approved', $expenseService);
        self::assertStringContainsString('RentalCalculationStatus::Approved', $expenseService);
        self::assertStringContainsString(
            'Reverse the approved rental calculation and its generated financial document before reversing this expense.',
            $expenseService,
        );
    }

    #[Test]
    public function vehicle_finance_is_not_classified_as_rental_revenue_or_cost(): void
    {
        $financeService = $this->source('app/Modules/VehicleRental/Services/VehicleFinanceService.php');

        self::assertSame('vehicle_finance', InvoiceType::VehicleFinance->value);
        self::assertSame(
            ['flat', 'reducing_balance', 'custom'],
            array_column(VehicleFinanceInterestMethod::cases(), 'value'),
        );
        self::assertSame(
            ['weekly', 'monthly', 'quarterly', 'yearly'],
            array_column(VehicleFinanceInstallmentFrequency::cases(), 'value'),
        );
        self::assertStringContainsString('InvoiceType::VehicleFinance', $financeService);
        self::assertStringNotContainsString('invoiceType: InvoiceType::Rental', $financeService);
    }

    #[Test]
    public function unavailable_vehicle_states_are_named_domain_options(): void
    {
        self::assertContains(VehicleStatus::UnderService, VehicleStatus::cases());
        self::assertContains(VehicleStatus::Rented, VehicleStatus::cases());
        self::assertContains(VehicleStatus::Blocked, VehicleStatus::cases());
    }

    private function source(string $relativePath): string
    {
        $source = file_get_contents($this->root.DIRECTORY_SEPARATOR.$relativePath);
        self::assertIsString($source, "Unable to read {$relativePath}.");

        return $source;
    }
}
