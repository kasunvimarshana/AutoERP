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
        $source = file_get_contents(base_path('app/Modules/VehicleRental/Services/RentalFinancialDocumentService.php'));
        self::assertIsString($source);
        self::assertStringContainsString('use Modules\\Invoice\\Models\\InvoiceSource;', $source);
        self::assertStringContainsString('new InvoiceSourceData(', $source);
        self::assertStringContainsString('new InvoiceSourceLineData(', $source);
        self::assertStringNotContainsString('vehicle_rental_calculation_invoice', $source);
        self::assertStringNotContainsString('RentalCalculationInvoice', $source);
    }

    public function test_rental_financial_creation_delegates_to_tax_invoice_and_finance_owners(): void
    {
        $source = file_get_contents(base_path('app/Modules/VehicleRental/Services/RentalFinancialDocumentService.php'));
        self::assertIsString($source);
        self::assertStringContainsString('TaxCalculationService $taxes', $source);
        self::assertStringContainsString('InvoiceCreationService $invoices', $source);
        self::assertStringContainsString('InvoiceStatusService $invoiceStatuses', $source);
        self::assertStringContainsString('InvoicePostingPlanFactory $postingPlans', $source);
        self::assertStringContainsString('InvoiceStatus::Approved', $source);
        self::assertStringContainsString('InvoiceStatus::Posted', $source);
    }

    public function test_reversed_financial_documents_release_invoice_source_capacity(): void
    {
        $source = file_get_contents(base_path('app/Modules/Invoice/Services/InvoiceSourceAllocationService.php'));
        self::assertIsString($source);
        self::assertStringContainsString('InvoiceStatus::Reversed->value', $source);
    }

    public function test_user_workflow_hands_settlement_to_the_payment_owner_module(): void
    {
        $financialPage = file_get_contents(base_path('resources/js/modules/vehicle-rental/pages/RentalFinancialDocumentsPage.tsx'));
        $settlementPage = file_get_contents(base_path('resources/js/modules/vehicle-rental/pages/RentalSettlementHandoffPage.tsx'));
        self::assertIsString($financialPage);
        self::assertIsString($settlementPage);
        self::assertStringContainsString('/payments/create?invoice_id=', $financialPage);
        self::assertStringContainsString('/payments/create?invoice_id=', $settlementPage);
        self::assertStringContainsString('Payment module', $settlementPage);
    }
}
