<?php

declare(strict_types=1);

namespace Tests\Unit\VehicleRental;

use PHPUnit\Framework\TestCase;

final class VehicleRentalHistoricalIntegrityContractTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = dirname(__DIR__, 3);
    }

    public function test_agreement_owned_history_does_not_cascade_delete(): void
    {
        foreach ($this->agreementOwnedHistoricalMigrations() as $migration) {
            $source = $this->source($migration);

            self::assertStringContainsString("->on('rental_agreements')", $source, "Migration {$migration} must reference rental agreements.");
            self::assertStringContainsString('->restrictOnDelete()', $source, "Migration {$migration} must restrict agreement deletes.");
            self::assertStringNotContainsString('->cascadeOnDelete()', $source, "Migration {$migration} must not cascade-delete agreement history.");
        }
    }

    public function test_agreement_party_shape_is_service_enforced(): void
    {
        $service = $this->source('app/Modules/VehicleRental/Services/RentalAgreementService.php');

        self::assertStringContainsString('Customer rental agreement requires only a customer.', $service);
        self::assertStringContainsString('Owner supply agreement requires only a supplier/vehicle owner.', $service);
        self::assertStringContainsString('Only draft agreements can be edited.', $service);
    }

    /** @return list<string> */
    private function agreementOwnedHistoricalMigrations(): array
    {
        return [
            'app/Modules/VehicleRental/Database/Migrations/2026_06_12_200003_create_rental_agreement_terms_table.php',
            'app/Modules/VehicleRental/Database/Migrations/2026_06_12_200004_create_rental_agreement_rate_versions_table.php',
            'app/Modules/VehicleRental/Database/Migrations/2026_06_12_200009_create_rental_vehicle_allocations_table.php',
        ];
    }

    private function source(string $relativePath): string
    {
        $source = file_get_contents($this->root.'/'.$relativePath);
        self::assertIsString($source);

        return $source;
    }
}
