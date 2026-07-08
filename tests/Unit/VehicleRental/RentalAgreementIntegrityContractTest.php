<?php

declare(strict_types=1);

namespace Tests\Unit\VehicleRental;

use PHPUnit\Framework\TestCase;

final class RentalAgreementIntegrityContractTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = dirname(__DIR__, 3);
    }

    public function test_agreement_writes_use_locked_optimistic_concurrency(): void
    {
        $service = $this->source('app/Modules/VehicleRental/Services/RentalAgreementService.php');
        $controller = $this->source('app/Modules/VehicleRental/Http/Controllers/RentalAgreementController.php');
        $updateRequest = $this->source('app/Modules/VehicleRental/Http/Requests/UpdateRentalAgreementRequest.php');
        $transitionRequest = $this->source('app/Modules/VehicleRental/Http/Requests/RentalTransitionRequest.php');
        $resource = $this->source('app/Modules/VehicleRental/Http/Resources/RentalAgreementResource.php');

        self::assertStringContainsString('DB::transaction', $service);
        self::assertStringContainsString('lockForUpdate()', $service);
        self::assertStringContainsString('assertExpectedVersion', $service);
        self::assertStringContainsString("'row_version' =>", $resource);
        self::assertStringContainsString('$agreement->row_version = $expectedVersion + 1;', $service);
        self::assertMatchesRegularExpression("/(?:'expected_version'\\s*=>|\\\$rules\\['expected_version'\\]\\s*=)\\s*\\['required',\\s*'integer',\\s*'min:1'\\]/", $updateRequest);
        self::assertMatchesRegularExpression("/'expected_version'\\s*=>\\s*\\['required',\\s*'integer',\\s*'min:1'\\]/", $transitionRequest);
        self::assertStringContainsString("validated('expected_version')", $controller);
    }

    public function test_database_service_and_configuration_own_agreement_party_deposit_and_timezone_invariants(): void
    {
        $migration = $this->source('app/Modules/VehicleRental/Database/Migrations/2026_06_12_200002_create_rental_agreements_table.php');
        $depositMigration = $this->source('app/Modules/VehicleRental/Database/Migrations/2026_06_12_200022_create_rental_deposit_requirements_table.php');
        $service = $this->source('app/Modules/VehicleRental/Services/RentalAgreementService.php');
        $configuration = $this->source('config/vehicle_rental.php');

        self::assertStringContainsString('rental_agreements_customer_id_tenant_fk', $migration);
        self::assertStringContainsString('rental_agreements_supplier_id_tenant_fk', $migration);
        self::assertStringContainsString('rental_agreements_id_tenant_kind_customer_uk', $migration);
        self::assertStringContainsString('rental_agreements_terminated_by_tenant_fk', $migration);
        self::assertStringContainsString('Customer rental agreement requires only a customer.', $service);
        self::assertStringContainsString('Owner supply agreement requires only a supplier/vehicle owner.', $service);
        self::assertStringContainsString('Security deposits are supported only for customer rental agreements.', $service);
        self::assertStringContainsString('agreement_kind', $depositMigration);
        self::assertStringContainsString("\$table->string('agreement_kind', 30)", $depositMigration);
        self::assertStringNotContainsString("\$table->enum('agreement_kind'", $depositMigration);
        self::assertStringContainsString('RentalAgreementKind::CustomerRental->value', $depositMigration);
        self::assertStringContainsString('rental_deposit_requirements_customer_id_tenant_fk', $depositMigration);
        self::assertStringContainsString('rental_deposit_req_agreement_kind_customer_fk', $depositMigration);
        self::assertStringNotContainsString('Asia/Colombo', $migration.$service.$configuration);
        self::assertStringContainsString("'vehicle_rental.billing_timezone'", $service);
        self::assertStringContainsString('VEHICLE_RENTAL_BILLING_TIMEZONE', $configuration);
    }

    public function test_rate_activation_never_rewrites_existing_active_periods(): void
    {
        $service = $this->source('app/Modules/VehicleRental/Services/RentalRateVersionService.php');
        $controller = $this->source('app/Modules/VehicleRental/Http/Controllers/RentalRateVersionController.php');
        $request = $this->source('app/Modules/VehicleRental/Http/Requests/ActivateRentalRateVersionRequest.php');
        $resource = $this->source('app/Modules/VehicleRental/Http/Resources/RentalRateVersionResource.php');

        self::assertStringContainsString('assertExpectedVersion', $service);
        self::assertStringContainsString('active immutable rate version', $service);
        self::assertStringContainsString('$version->row_version = $expectedVersion + 1;', $service);
        self::assertStringContainsString("return \$version->refresh()->load('components');", $service);
        self::assertStringNotContainsString('$active->effective_to =', $service);
        self::assertStringNotContainsString('$active->status = RentalRateVersionStatus::Superseded;', $service);
        self::assertMatchesRegularExpression("/'expected_version'\\s*=>\\s*\\['required',\\s*'integer',\\s*'min:1'\\]/", $request);
        self::assertStringContainsString("validated('expected_version')", $controller);
        self::assertStringContainsString("'row_version' =>", $resource);
    }

    public function test_frontend_sends_the_loaded_version_for_agreement_commands(): void
    {
        $api = $this->source('resources/js/modules/vehicle-rental/vehicleRentalApi.ts');
        $detail = $this->source('resources/js/modules/vehicle-rental/pages/RentalAgreementDetailPage.tsx');

        self::assertStringContainsString('expected_version: expectedVersion', $api);
        self::assertStringContainsString('result.data.row_version', $detail);
        self::assertStringContainsString('transitionRentalAgreement(', $detail);
    }

    private function source(string $relativePath): string
    {
        $source = file_get_contents($this->root.'/'.$relativePath);
        self::assertIsString($source);

        return $source;
    }
}
