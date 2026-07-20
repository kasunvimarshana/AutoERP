<?php

declare(strict_types=1);

namespace Tests\Unit\VehicleRental;

use Modules\VehicleRental\Providers\VehicleRentalServiceProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class RentalLegalDocumentAvailabilityPolicyTest extends TestCase
{
    public function test_rental_assignment_does_not_register_a_legal_document_availability_blocker(): void
    {
        $providerFile = (new ReflectionClass(VehicleRentalServiceProvider::class))->getFileName();
        self::assertIsString($providerFile);

        $providerSource = file_get_contents($providerFile);
        self::assertIsString($providerSource);
        self::assertStringNotContainsString('RentalLegalDocumentAvailabilityBlocker', $providerSource);

        self::assertFileDoesNotExist(
            dirname($providerFile, 2).'/Services/Availability/RentalLegalDocumentAvailabilityBlocker.php',
        );
    }
}
