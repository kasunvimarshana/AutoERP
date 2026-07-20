<?php

declare(strict_types=1);

namespace Tests\Unit\VehicleRental;

use Modules\Vehicle\Enums\VehicleDocumentType;
use Modules\VehicleRental\Services\Availability\RentalLegalDocumentAvailabilityBlocker;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class RentalLegalDocumentAvailabilityPolicyTest extends TestCase
{
    public function test_rental_assignment_requires_revenue_license_but_not_insurance(): void
    {
        $requiredDocumentTypes = (new ReflectionClass(RentalLegalDocumentAvailabilityBlocker::class))
            ->getConstant('REQUIRED_DOCUMENT_TYPES');

        self::assertSame([VehicleDocumentType::RevenueLicense], $requiredDocumentTypes);
        self::assertNotContains(VehicleDocumentType::Insurance, $requiredDocumentTypes);
    }
}
