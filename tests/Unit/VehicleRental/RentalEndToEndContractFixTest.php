<?php

declare(strict_types=1);

namespace Tests\Unit\VehicleRental;

use PHPUnit\Framework\TestCase;

final class RentalEndToEndContractFixTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = dirname(__DIR__, 3);
    }

    public function test_mutating_rental_commands_are_version_aware_end_to_end(): void
    {
        $api = $this->source('resources/js/modules/vehicle-rental/vehicleRentalApi.ts');
        $reservationResource = $this->source('app/Modules/VehicleRental/Http/Resources/RentalReservationResource.php');
        $expenseResource = $this->source('app/Modules/VehicleRental/Http/Resources/RentalExpenseResource.php');
        $custodyResource = $this->source('app/Modules/VehicleRental/Http/Resources/RentalCustodyEventResource.php');
        $financeResource = $this->source('app/Modules/VehicleRental/Http/Resources/VehicleFinanceAgreementResource.php');
        $depositResource = $this->source('app/Modules/VehicleRental/Http/Resources/RentalDepositRequirementResource.php');

        self::assertStringContainsString('transitionRentalReservation', $api);
        self::assertStringContainsString('confirmRentalCustodyEvent = (id: number, expectedVersion: number)', $api);
        self::assertStringContainsString('transitionRentalExpense', $api);
        self::assertStringContainsString('createRentalInvoice', $api);
        self::assertStringContainsString('activateVehicleFinanceAgreement', $api);
        self::assertStringContainsString('createVehicleFinancePayable', $api);
        self::assertGreaterThanOrEqual(6, substr_count($api, 'expected_version: expectedVersion'));

        foreach ([$reservationResource, $expenseResource, $custodyResource, $financeResource, $depositResource] as $resource) {
            self::assertStringContainsString("'row_version' =>", $resource);
        }

        self::assertStringContainsString("'payment' =>", $depositResource);
        self::assertStringContainsString("'invoice' =>", $depositResource);
        self::assertStringNotContainsString("'payment_id' =>", $depositResource);
        self::assertStringNotContainsString("'invoice_id' =>", $depositResource);
    }

    public function test_reservation_conversion_and_lookup_contracts_use_real_rental_semantics(): void
    {
        $storeAgreementRequest = $this->source('app/Modules/VehicleRental/Http/Requests/StoreRentalAgreementRequest.php');
        $agreementService = $this->source('app/Modules/VehicleRental/Services/RentalAgreementService.php');
        $agreementCreatePage = $this->source('resources/js/modules/vehicle-rental/pages/RentalAgreementCreatePage.tsx');
        $lookups = $this->source('resources/js/modules/vehicle-rental/components/RentalLookups.tsx');

        self::assertStringContainsString('expected_reservation_version', $storeAgreementRequest);
        self::assertStringContainsString('assertReservationExpectedVersion', $agreementService);
        self::assertStringContainsString('RentalReservationStatus::Converted', $agreementService);
        self::assertStringContainsString('getRentalReservation', $agreementCreatePage);
        self::assertStringContainsString('expected_reservation_version: reservation?.row_version', $agreementCreatePage);
        self::assertStringNotContainsString('Asia/Colombo', $agreementCreatePage);

        self::assertStringContainsString("'customer_rental'", $lookups);
        self::assertStringContainsString("'owner_supply'", $lookups);
        self::assertStringNotContainsString('agreement_kind: direction', $lookups);
    }

    public function test_reporting_and_frontend_text_have_single_current_contracts(): void
    {
        $catalog = $this->source('app/Modules/Reporting/Services/ReportCatalog.php');
        $rentalUi = implode("\n", array_map(
            fn (string $relative): string => $this->source($relative),
            [
                'resources/js/modules/vehicle-rental/pages/RentalAgreementCreatePage.tsx',
                'resources/js/modules/vehicle-rental/pages/RentalDepositPage.tsx',
                'resources/js/modules/vehicle-rental/pages/RentalRunningChartPage.tsx',
                'resources/js/modules/vehicle-rental/pages/RentalDashboardPage.tsx',
            ],
        ));

        self::assertStringNotContainsString("key: 'vehicle-rental.running-chart'", $catalog);
        self::assertStringNotContainsString("key: 'vehicle-rental.driver-overtime'", $catalog);
        self::assertDoesNotMatchRegularExpression('/[\x{00E2}\x{00C2}\x{2013}\x{2014}\x{2192}\x{00B7}]/u', $rentalUi);
    }

    private function source(string $relativePath): string
    {
        $source = file_get_contents($this->root.'/'.$relativePath);
        self::assertIsString($source);

        return $source;
    }
}
