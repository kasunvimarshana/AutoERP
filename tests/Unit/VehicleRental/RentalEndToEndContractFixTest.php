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
        $allocationResource = $this->source('app/Modules/VehicleRental/Http/Resources/RentalAllocationResource.php');
        $storeAllocationRequest = $this->source('app/Modules/VehicleRental/Http/Requests/StoreRentalAllocationRequest.php');
        $storeRateVersionRequest = $this->source('app/Modules/VehicleRental/Http/Requests/StoreRentalRateVersionRequest.php');
        $assignDriverRequest = $this->source('app/Modules/VehicleRental/Http/Requests/AssignRentalDriverRequest.php');
        $replacementRequest = $this->source('app/Modules/VehicleRental/Http/Requests/StoreRentalReplacementRequest.php');
        $storeCustodyRequest = $this->source('app/Modules/VehicleRental/Http/Requests/StoreRentalCustodyEventRequest.php');
        $storeUsageRequest = $this->source('app/Modules/VehicleRental/Http/Requests/StoreRentalUsageRequest.php');
        $forfeitRequest = $this->source('app/Modules/VehicleRental/Http/Requests/ForfeitRentalDepositRequest.php');
        $depositService = $this->source('app/Modules/VehicleRental/Services/RentalDepositService.php');
        $depositPage = $this->source('resources/js/modules/vehicle-rental/pages/RentalDepositPage.tsx');
        $usageService = $this->source('app/Modules/VehicleRental/Services/RentalUsageService.php');
        $runningChartPage = $this->source('resources/js/modules/vehicle-rental/pages/RentalRunningChartPage.tsx');
        $usageLogResource = $this->source('app/Modules/VehicleRental/Http/Resources/RentalUsageLogResource.php');
        $usageFactResource = $this->source('app/Modules/VehicleRental/Http/Resources/RentalUsageFactResource.php');
        $lookups = $this->source('resources/js/modules/vehicle-rental/components/RentalLookups.tsx');
        $types = $this->source('resources/js/modules/vehicle-rental/vehicleRentalTypes.ts');

        self::assertStringContainsString('transitionRentalReservation', $api);
        self::assertStringContainsString('createRentalAllocation = (', $api);
        self::assertStringContainsString('expected_agreement_version: expectedAgreementVersion', $api);
        self::assertStringContainsString('assignRentalDriver = (', $api);
        self::assertStringContainsString('cancelRentalAllocation', $api);
        self::assertStringContainsString('replaceRentalVehicle = (', $api);
        self::assertStringContainsString('expected_allocation_version: expectedAllocationVersion', $api);
        self::assertStringContainsString('createRentalCustodyEvent = (', $api);
        self::assertStringContainsString('createRentalUsageLog = (', $api);
        self::assertStringContainsString('confirmRentalCustodyEvent = (id: number, expectedVersion: number)', $api);
        self::assertStringContainsString('transitionRentalExpense', $api);
        self::assertStringContainsString('createRentalInvoice', $api);
        self::assertStringContainsString('activateVehicleFinanceAgreement', $api);
        self::assertStringContainsString('createVehicleFinancePayable', $api);
        self::assertStringContainsString('createRentalRateVersion = (', $api);
        self::assertStringContainsString('expected_agreement_version: expectedAgreementVersion', $api);
        self::assertStringContainsString('reverseRentalDepositLink', $api);
        self::assertGreaterThanOrEqual(6, substr_count($api, 'expected_version: expectedVersion'));

        foreach ([$reservationResource, $expenseResource, $custodyResource, $financeResource, $depositResource, $allocationResource] as $resource) {
            self::assertStringContainsString("'row_version' =>", $resource);
        }

        self::assertStringContainsString("'expected_agreement_version' => ['required'", $storeAllocationRequest);
        self::assertStringContainsString("'expected_source_allocation_version' => ['nullable', 'required_with:source_allocation_id'", $storeAllocationRequest);
        self::assertStringContainsString("'expected_finance_agreement_version' => ['nullable', 'required_with:vehicle_finance_agreement_id'", $storeAllocationRequest);
        self::assertStringNotContainsString("'replaces_allocation_id'", $storeAllocationRequest);
        self::assertStringContainsString("'expected_agreement_version' => ['required'", $storeRateVersionRequest);
        self::assertStringContainsString("'expected_version' => ['required'", $assignDriverRequest);
        self::assertStringContainsString("'expected_allocation_version' => ['required'", $replacementRequest);
        self::assertStringContainsString("'expected_agreement_version' => ['required'", $replacementRequest);
        self::assertStringContainsString("'expected_source_allocation_version' => ['nullable', 'required_with:source_allocation_id'", $replacementRequest);
        self::assertStringContainsString("'expected_finance_agreement_version' => ['nullable', 'required_with:vehicle_finance_agreement_id'", $replacementRequest);
        self::assertStringContainsString("'expected_allocation_version' => ['required'", $storeCustodyRequest);
        self::assertStringContainsString("'expected_allocation_version' => ['required'", $storeUsageRequest);
        self::assertStringContainsString("'expected_source_allocation_version' => ['nullable'", $storeUsageRequest);
        self::assertStringContainsString("'events.*.unit' => ['prohibited']", $storeUsageRequest);
        self::assertStringContainsString('expected_source_allocation_version:', $runningChartPage);
        self::assertStringContainsString('lockSourceAllocation', $usageService);
        self::assertStringContainsString('Owner-applicable usage events require a linked owner supply allocation.', $usageService);
        self::assertStringContainsString('resolveEventUnit', $usageService);
        self::assertStringContainsString('rateComponentUnit', $usageService);
        self::assertStringContainsString('Usage event unit is ambiguous because customer and owner rate components use different units.', $usageService);
        self::assertStringNotContainsString('unit: row.unit || null', $runningChartPage);
        self::assertStringNotContainsString('label="Unit"', $runningChartPage);
        self::assertStringContainsString("'rate_version' =>", $usageLogResource);
        self::assertStringNotContainsString("'rate_version_id' =>", $usageLogResource);
        self::assertStringContainsString("'context' =>", $usageFactResource);
        self::assertStringContainsString("'usage_log' =>", $usageFactResource);
        self::assertStringNotContainsString("'context_id' =>", $usageFactResource);
        self::assertStringNotContainsString("'usage_log_id' =>", $usageFactResource);

        self::assertStringContainsString("'payment' =>", $depositResource);
        self::assertStringContainsString("'invoice' =>", $depositResource);
        self::assertStringContainsString("'reverses_link' =>", $depositResource);
        self::assertStringContainsString("'customer' =>", $depositResource);
        self::assertStringContainsString('customer?: RentalParty | null;', $types);
        self::assertStringContainsString('currency?: RentalCurrency | null;', $types);
        self::assertStringNotContainsString("'payment_id' =>", $depositResource);
        self::assertStringNotContainsString("'invoice_id' =>", $depositResource);
        self::assertStringNotContainsString("'reverses_link_id' =>", $depositResource);
        self::assertStringContainsString('InvoiceLookupFilters', $lookups);
        self::assertStringContainsString('party_id: filters.partyId ?? undefined', $lookups);
        self::assertStringContainsString('invoiceType="rental"', $depositPage);
        self::assertStringContainsString('partyId={selected.customer?.id ?? null}', $depositPage);
        self::assertStringContainsString("'expected_payment_version' => ['required', 'integer', 'min:1']", $forfeitRequest);
        self::assertStringContainsString("'allocation_date' => ['required', 'date']", $forfeitRequest);
        self::assertStringContainsString('RentalDepositLinkType::Forfeiture->value', $depositService);
        self::assertStringContainsString('payment_id: selectedReceiptLink.payment.id', $depositPage);
        self::assertStringContainsString('expected_payment_version: selectedReceiptLink.payment.row_version', $depositPage);
        self::assertStringContainsString('allocation_date: businessDateInputValue()', $depositPage);
        self::assertStringContainsString("'fingerprint' => \$this->linkFingerprint", $depositService);
        self::assertStringContainsString('linkFingerprint', $depositService);
        self::assertStringContainsString('A deposit reversal link cannot be reversed again.', $depositService);
        self::assertStringContainsString('reverseRentalDepositLink(link.id, selected.row_version)', $depositPage);
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
        self::assertStringContainsString("agreementKind?: 'customer_rental' | 'owner_supply'", $lookups);
    }

    public function test_vehicle_allocation_lifecycle_contracts_are_conflict_safe_and_guided(): void
    {
        $routes = $this->source('app/Modules/VehicleRental/Routes/api.php');
        $availability = $this->source('app/Modules/VehicleRental/Services/RentalAvailabilityService.php');
        $allocationService = $this->source('app/Modules/VehicleRental/Services/RentalAllocationService.php');
        $custodyService = $this->source('app/Modules/VehicleRental/Services/RentalCustodyService.php');
        $replacementService = $this->source('app/Modules/VehicleRental/Services/RentalReplacementService.php');
        $migration = $this->source('app/Modules/VehicleRental/Database/Migrations/2026_06_12_200009_create_rental_vehicle_allocations_table.php');
        $lookups = $this->source('resources/js/modules/vehicle-rental/components/RentalLookups.tsx');
        $allocationPage = $this->source('resources/js/modules/vehicle-rental/pages/RentalAllocationPage.tsx');
        $replacementPage = $this->source('resources/js/modules/vehicle-rental/pages/RentalReplacementPage.tsx');
        $detailPage = $this->source('resources/js/modules/vehicle-rental/pages/RentalAllocationDetailPage.tsx');
        $custodyResource = $this->source('app/Modules/VehicleRental/Http/Resources/RentalCustodyEventResource.php');
        $custodyRequest = $this->source('app/Modules/VehicleRental/Http/Requests/StoreRentalCustodyEventRequest.php');
        $custodyPage = $this->source('resources/js/modules/vehicle-rental/pages/RentalCustodyPage.tsx');

        self::assertStringContainsString("allocations/{allocation}/cancel", $routes);
        self::assertStringContainsString('->lockForUpdate()', $availability);
        self::assertStringContainsString('assertSourceContract', $allocationService);
        self::assertStringContainsString("requiredExpectedVersion(\$data, 'expected_source_allocation_version')", $allocationService);
        self::assertStringContainsString("requiredExpectedVersion(\$data, 'expected_finance_agreement_version')", $allocationService);
        self::assertStringContainsString('Driver assignment requires a planned or active vehicle allocation.', $allocationService);
        self::assertStringContainsString('closed_by = $userId', $allocationService);
        self::assertStringContainsString('activated_by = $userId', $allocationService);
        self::assertStringContainsString('Only allocations under an active rental agreement can be activated.', $allocationService);
        self::assertStringContainsString('assertActiveSourceAllocationForActivation', $allocationService);
        self::assertStringContainsString('Customer allocation requires an active owner source allocation covering the full allocation period.', $allocationService);
        self::assertStringContainsString('lockCustodyTimeline', $custodyService);
        self::assertStringContainsString('assertReplacementEventMatchesAllocation', $custodyService);
        self::assertStringContainsString('Vehicle cannot be returned to its owner while a customer allocation is planned or active.', $custodyService);
        self::assertGreaterThanOrEqual(2, substr_count($custodyService, '$this->allocations->activate($event->allocation, $userId)'));
        self::assertStringContainsString('PUBLIC_EVENT_TYPES', $custodyRequest);
        self::assertStringContainsString("'replacement_id' => ['prohibited']", $custodyRequest);
        self::assertStringNotContainsString('Rule::enum(RentalCustodyEventType::class)', $custodyRequest);
        self::assertStringContainsString('allocationRefresh', $custodyPage);
        self::assertStringContainsString('rental_vehicle_allocations_activated_by_tenant_fk', $migration);
        self::assertStringContainsString('$this->custody->confirm($returnEvent, (int) $returnEvent->row_version, $userId);', $replacementService);
        self::assertStringContainsString('$this->custody->confirm($handoverEvent, (int) $handoverEvent->row_version, $userId);', $replacementService);
        self::assertStringContainsString("'expected_source_allocation_version' => \$data['expected_source_allocation_version'] ?? null", $replacementService);
        self::assertStringContainsString("'expected_finance_agreement_version' => \$data['expected_finance_agreement_version'] ?? null", $replacementService);
        self::assertStringContainsString('Company-owned allocations require a company vehicle ownership record.', $allocationService);
        self::assertStringNotContainsString("'attachments'", $custodyService);
        self::assertStringContainsString("'old_return.items.*.item_type'", $this->source('app/Modules/VehicleRental/Http/Requests/StoreRentalReplacementRequest.php'));
        self::assertStringContainsString('replacementDrivers', $replacementService);
        self::assertStringContainsString('RentalAvailableVehicleLookupSelect', $lookups);
        self::assertStringContainsString('covers_start_at', $lookups);
        self::assertStringContainsString('activeOnly', $lookups);
        self::assertStringContainsString('row_version: allocation.row_version', $lookups);
        self::assertStringContainsString('row_version: agreement.row_version', $lookups);
        self::assertStringContainsString('RentalAvailableVehicleLookupSelect', $allocationPage);
        self::assertStringContainsString('agreementDetails.row_version', $allocationPage);
        self::assertStringContainsString('expected_source_allocation_version:', $allocationPage);
        self::assertStringContainsString('expected_finance_agreement_version:', $allocationPage);
        self::assertStringContainsString('Company vehicle ownership', $allocationPage);
        self::assertStringContainsString("startOdometer: ''", $allocationPage);
        self::assertStringContainsString('start_odometer: form.startOdometer || null', $allocationPage);
        self::assertStringNotContainsString("startOdometer: '0'", $allocationPage);
        self::assertStringContainsString('RentalAvailableVehicleLookupSelect', $replacementPage);
        self::assertStringContainsString('allocation.data.row_version', $replacementPage);
        self::assertStringContainsString('expected_source_allocation_version:', $replacementPage);
        self::assertStringContainsString('expected_finance_agreement_version:', $replacementPage);
        self::assertStringContainsString('Company vehicle ownership', $replacementPage);
        self::assertStringContainsString("'row_version', 'allocation_number', 'status', 'allocated_from', 'allocated_to'", $this->source('app/Modules/VehicleRental/Http/Resources/RentalAllocationResource.php'));
        self::assertStringContainsString("'row_version', 'agreement_number', 'status', 'starts_at', 'matures_at'", $this->source('app/Modules/VehicleRental/Http/Resources/RentalAllocationResource.php'));
        self::assertStringContainsString('cancelRentalAllocation', $detailPage);
        self::assertStringContainsString('assignRentalDriver(row.id, row.row_version', $detailPage);
        self::assertStringContainsString('GenericLookupSelect<EmployeeSummary>', $detailPage);
        self::assertStringContainsString('searchEmployees', $detailPage);
        self::assertStringContainsString('employee_id: selectedDriver.id', $detailPage);
        self::assertStringContainsString('assigned_from: driverForm.assignedFrom', $detailPage);
        self::assertStringContainsString('Source allocation', $detailPage);
        self::assertStringContainsString('Finance agreement', $detailPage);
        self::assertStringContainsString("'replacement' =>", $custodyResource);
        self::assertStringNotContainsString("'replacement_id' =>", $custodyResource);
    }

    public function test_finance_and_calculation_payloads_match_supported_backend_contracts(): void
    {
        $financeService = $this->source('app/Modules/VehicleRental/Services/VehicleFinanceService.php');
        $financeRequest = $this->source('app/Modules/VehicleRental/Http/Requests/StoreVehicleFinanceAgreementRequest.php');
        $financePage = $this->source('resources/js/modules/vehicle-rental/pages/VehicleFinancePage.tsx');
        $calculationResource = $this->source('app/Modules/VehicleRental/Http/Resources/RentalCalculationRunResource.php');
        $agreementResource = $this->source('app/Modules/VehicleRental/Http/Resources/RentalAgreementResource.php');
        $types = $this->source('resources/js/modules/vehicle-rental/vehicleRentalTypes.ts');

        self::assertStringContainsString('generateReducingBalanceSchedule', $financeService);
        self::assertStringContainsString('INSTALLMENT_PERIODS_PER_YEAR', $financeService);
        self::assertStringContainsString("'schedule' => ['required_if:interest_method,custom', 'prohibited_unless:interest_method,custom'", $financeRequest);
        self::assertStringContainsString('"reducing_balance"', $financePage);
        self::assertStringNotContainsString('"custom"', $financePage);

        self::assertStringContainsString("'reservation' =>", $agreementResource);
        self::assertStringNotContainsString("'reservation_id' =>", $agreementResource);
        self::assertStringContainsString("'source' => \$this->lineSource(\$line)", $calculationResource);
        self::assertStringContainsString('private function lineSource', $calculationResource);
        self::assertStringNotContainsString("'source_id' =>", $calculationResource);
        self::assertStringContainsString('source: {', $types);
        self::assertStringNotContainsString('source_id: number;', $types);
        self::assertStringContainsString('reverses_link?:', $types);
        self::assertStringContainsString('interest_method: string;', $types);
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

    public function test_running_chart_records_one_physical_usage_with_two_guarded_commercial_sides(): void
    {
        $usageService = $this->source('app/Modules/VehicleRental/Services/RentalUsageService.php');
        $usageFactService = $this->source('app/Modules/VehicleRental/Services/RentalUsageFactService.php');
        $rateVersionService = $this->source('app/Modules/VehicleRental/Services/RentalRateVersionService.php');
        $usageLogMigration = $this->source('app/Modules/VehicleRental/Database/Migrations/2026_06_12_200014_create_rental_usage_logs_table.php');
        $usageLogModel = $this->source('app/Modules/VehicleRental/Models/RentalUsageLog.php');
        $allocationResource = $this->source('app/Modules/VehicleRental/Http/Resources/RentalAllocationResource.php');
        $runningChartPage = $this->source('resources/js/modules/vehicle-rental/pages/RentalRunningChartPage.tsx');
        $agreementDetailPage = $this->source('resources/js/modules/vehicle-rental/pages/RentalAgreementDetailPage.tsx');
        $usageLogResource = $this->source('app/Modules/VehicleRental/Http/Resources/RentalUsageLogResource.php');
        $lookups = $this->source('resources/js/modules/vehicle-rental/components/RentalLookups.tsx');

        self::assertStringContainsString('AGREEMENT_KIND_CUSTOMER_RENTAL', $runningChartPage);
        self::assertStringContainsString('agreementKind={AGREEMENT_KIND_CUSTOMER_RENTAL}', $runningChartPage);
        self::assertStringContainsString('RentalAllocationLookupSelect', $runningChartPage);
        self::assertStringContainsString('getRentalAllocation', $runningChartPage);
        self::assertStringContainsString('status="active"', $runningChartPage);
        self::assertStringNotContainsString('per_page: 100', $runningChartPage);
        self::assertStringContainsString('status: filters.status ?? undefined', $lookups);
        self::assertStringContainsString('RENTAL_MODE_WITH_DRIVER', $runningChartPage);
        self::assertStringContainsString('required={requiresDriverAssignment}', $runningChartPage);
        self::assertStringContainsString('selectedAllocation.source_allocation?.row_version', $runningChartPage);
        self::assertStringContainsString('APPLICABILITY_OWNER', $runningChartPage);
        self::assertStringContainsString('APPLICABILITY_BOTH', $runningChartPage);
        self::assertStringContainsString("'rental_mode'", $allocationResource);

        self::assertStringContainsString('assertSourceAllocation', $usageService);
        self::assertStringContainsString('Running chart requires an active rental agreement.', $usageService);
        self::assertStringContainsString('Running chart owner payable context requires an active owner supply allocation.', $usageService);
        self::assertStringContainsString('Running chart owner payable context requires an active owner supply agreement.', $usageService);
        self::assertStringContainsString('Usage time must be inside the owner supply allocation period.', $usageService);
        self::assertStringContainsString('RentalDriverAssignmentStatus::Active->value', $usageService);
        self::assertStringContainsString('Driver assignment is not active for the complete usage period.', $usageService);
        self::assertStringContainsString('$this->rates->assertSingleVersionCoversPeriod', $usageService);
        self::assertStringContainsString('$this->rates->assertSingleVersionCoversPeriod', $usageFactService);
        self::assertStringContainsString("'context.agreement'", $usageFactService);
        self::assertStringContainsString('assertSingleVersionCoversPeriod', $rateVersionService);
        self::assertStringContainsString('Usage period must stay inside one active rental rate version.', $rateVersionService);
        self::assertStringContainsString('assertNoUsageCrossesRateBoundaries', $rateVersionService);
        self::assertStringContainsString('Rate activation would split existing running-chart usage.', $rateVersionService);
        self::assertStringContainsString('fingerprint_sequence', $usageService);
        self::assertStringContainsString('fingerprint_sequence', $usageLogMigration);
        self::assertStringContainsString('fingerprint_sequence', $usageLogModel);
        self::assertStringContainsString("where('fingerprint', \$fingerprint)", $usageService);
        self::assertStringContainsString('RentalUsageStatus::Reversed', $usageService);
        self::assertStringContainsString("'agreement_id'", $this->source('app/Modules/VehicleRental/Http/Requests/ListRentalRequest.php'));
        self::assertStringContainsString("'financial_side'", $this->source('app/Modules/VehicleRental/Http/Requests/ListRentalRequest.php'));
        self::assertStringContainsString("! empty(\$filters['agreement_id']) && ! empty(\$filters['financial_side'])", $usageService);
        self::assertStringContainsString("->where('agreement_id', \$filters['agreement_id'])", $usageService);
        self::assertStringContainsString("->where('financial_side', \$filters['financial_side'])", $usageService);
        self::assertStringContainsString("'contexts.allocation'", $usageService);
        self::assertStringContainsString("'allocation' =>", $usageLogResource);
        self::assertStringContainsString("'source_allocation_id' => \$sourceAllocation?->getKey()", $usageService);
        self::assertStringContainsString("'events' => \$events", $usageService);
        self::assertStringContainsString("'garage_distance_km' => \$garage", $usageService);
        self::assertStringContainsString('Commercial distance cannot exceed the physical net operational distance.', $usageFactService);
        self::assertStringContainsString("'context.rateVersion'", $usageFactService);
        self::assertStringContainsString('AgreementRunningChartPanel', $agreementDetailPage);
        self::assertStringContainsString('listRentalUsageLogs', $agreementDetailPage);
        self::assertStringContainsString('agreement_id: agreementId', $agreementDetailPage);
        self::assertStringContainsString('financial_side: side', $agreementDetailPage);
        self::assertStringContainsString('rentalAgreementFinancialSide(row.agreement_kind)', $agreementDetailPage);
        self::assertStringContainsString('usageContextForSide(row, side)?.allocation', $agreementDetailPage);

        $calculationService = $this->source('app/Modules/VehicleRental/Services/RentalCalculationService.php');
        $usageEventBillingMap = $this->source('app/Modules/VehicleRental/Services/RentalUsageEventBillingMap.php');
        $rateComponentCode = $this->source('app/Modules/VehicleRental/Enums/RentalRateComponentCode.php');
        $agreementCreatePage = $this->source('resources/js/modules/vehicle-rental/pages/RentalAgreementCreatePage.tsx');

        self::assertStringContainsString("case Pass = 'pass';", $rateComponentCode);
        self::assertStringContainsString('RentalRateComponentCode::Pass => RentalUsageEventType::Pass', $usageEventBillingMap);
        self::assertStringContainsString('eventQuantityForComponent($contexts, $component->component_code)', $calculationService);
        self::assertStringContainsString('eventComponentDefaults', $agreementCreatePage);
        self::assertStringContainsString('["pass", "count"]', $agreementCreatePage);
    }

    private function source(string $relativePath): string
    {
        $source = file_get_contents($this->root.'/'.$relativePath);
        self::assertIsString($source);

        return $source;
    }
}
