<?php

declare(strict_types=1);

namespace Tests\Unit\VehicleRental;

use Modules\Finance\Enums\FinanceAccountRoleCode;
use Modules\Finance\Enums\FinancePostingProfileCode;
use Modules\Invoice\Enums\InvoiceType;
use Modules\VehicleRental\Constants\VehicleRentalFinancialDocument;
use Modules\VehicleRental\Constants\VehicleRentalSource;
use PHPUnit\Framework\TestCase;

final class RentalFinancialWorkflowContractTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__.'/../../..';

    public function test_rental_invoice_lifecycle_is_active_while_vehicle_finance_remains_retired(): void
    {
        self::assertFalse(InvoiceType::Rental->belongsToRetiredSourceModule());
        self::assertTrue(InvoiceType::VehicleFinance->belongsToRetiredSourceModule());
    }

    public function test_financial_semantics_are_named_in_owner_module_enums_and_constants(): void
    {
        self::assertSame('customer_rental_invoice', FinancePostingProfileCode::CustomerRentalInvoice->value);
        self::assertSame('supplier_rental_invoice', FinancePostingProfileCode::SupplierRentalInvoice->value);
        self::assertSame('rental_deposit', FinancePostingProfileCode::RentalDeposit->value);
        self::assertSame('rental_revenue', FinanceAccountRoleCode::RentalRevenue->value);
        self::assertSame('rental_expense', FinanceAccountRoleCode::RentalExpense->value);
        self::assertSame('vehicle_rental_calculation', VehicleRentalSource::CALCULATION_DOCUMENT);
        self::assertSame('vehicle_rental_calculation_line', VehicleRentalSource::CALCULATION_LINE_DOCUMENT);
        self::assertSame('1.000000', VehicleRentalFinancialDocument::DEFAULT_EXCHANGE_RATE);
    }

    public function test_rental_reuses_invoice_source_as_the_single_financial_document_relationship(): void
    {
        $service = $this->source('app/Modules/VehicleRental/Services/RentalFinancialDocumentService.php');
        $factory = $this->source('app/Modules/VehicleRental/Services/RentalFinancialDocumentDataFactory.php');

        self::assertStringContainsString('use Modules\\Invoice\\Models\\InvoiceSource;', $service);
        self::assertStringContainsString('new InvoiceSourceData(', $factory);
        self::assertStringContainsString('new InvoiceSourceLineData(', $factory);
        self::assertStringNotContainsString('vehicle_rental_calculation_invoice', $service.$factory);
        self::assertStringNotContainsString('RentalCalculationInvoice', $service.$factory);
    }

    public function test_rental_financial_creation_delegates_to_tax_invoice_and_finance_owners(): void
    {
        $service = $this->source('app/Modules/VehicleRental/Services/RentalFinancialDocumentService.php');
        $factory = $this->source('app/Modules/VehicleRental/Services/RentalFinancialDocumentDataFactory.php');

        self::assertStringContainsString('TaxCalculationService $taxes', $factory);
        self::assertStringContainsString('InvoicePostingPlanFactory $postingPlans', $factory);
        self::assertStringContainsString('InvoiceCreationService $invoices', $service);
        self::assertStringContainsString('InvoiceStatusService $invoiceStatuses', $service);
        self::assertStringContainsString('InvoiceStatus::Approved', $service);
        self::assertStringContainsString('InvoiceStatus::Posted', $service);
    }

    public function test_reversed_financial_documents_release_invoice_source_capacity(): void
    {
        $source = $this->source('app/Modules/Invoice/Services/InvoiceSourceAllocationService.php');

        self::assertStringContainsString('InvoiceStatus::Reversed->value', $source);
    }

    public function test_user_workflow_hands_settlement_to_the_payment_owner_module(): void
    {
        $financialPage = $this->source('resources/js/modules/vehicle-rental/pages/RentalFinancialDocumentsPage.tsx');
        $settlementPage = $this->source('resources/js/modules/vehicle-rental/pages/RentalSettlementHandoffPage.tsx');

        self::assertStringContainsString('/payments/create?invoice_id=', $financialPage);
        self::assertStringContainsString('/payments/create?invoice_id=', $settlementPage);
        self::assertStringContainsString('Payment module', $settlementPage);
    }

    private function source(string $relativePath): string
    {
        $source = file_get_contents(self::PROJECT_ROOT.'/'.$relativePath);
        self::assertIsString($source, "Unable to read project source file [{$relativePath}].");

        return $source;
    }
}
