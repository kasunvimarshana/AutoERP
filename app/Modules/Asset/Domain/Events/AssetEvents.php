<?php declare(strict_types=1);

namespace Modules\Asset\Domain\Events;

/**
 * VehicleCreated - Domain event when a vehicle is created
 */
final class VehicleCreated
{
    public function __construct(
        public string $vehicleId,
        public string $tenantId,
        public string $vin,
        public string $registrationPlate,
    ) {}
}

/**
 * VehicleStatusChanged - Domain event when vehicle status changes
 */
final class VehicleStatusChanged
{
    public function __construct(
        public string $vehicleId,
        public string $tenantId,
        public string $oldStatus,
        public string $newStatus,
    ) {}
}

/**
 * VehicleMileageUpdated - Domain event when vehicle mileage is recorded
 */
final class VehicleMileageUpdated
{
    public function __construct(
        public string $vehicleId,
        public string $tenantId,
        public int $oldMileage,
        public int $newMileage,
    ) {}
}

/**
 * AssetDepreciationCalculated - Domain event when depreciation is calculated
 */
final class AssetDepreciationCalculated
{
    public function __construct(
        public string $assetId,
        public string $tenantId,
        public string $depreciationAmount,
        public string $bookValue,
        public int $year,
        public int $month,
    ) {}
}

/**
 * VehicleDocumentExpiring - Domain event when vehicle document is about to expire
 */
final class VehicleDocumentExpiring
{
    public function __construct(
        public string $vehicleId,
        public string $tenantId,
        public string $documentType,
        public string $documentNumber,
        public \DateTime $expiryDate,
    ) {}
}

/**
 * VehicleDocumentExpired - Domain event when vehicle document expires
 */
final class VehicleDocumentExpired
{
    public function __construct(
        public string $vehicleId,
        public string $tenantId,
        public string $documentType,
        public string $documentNumber,
    ) {}
}

/**
 * VehicleRetired - Domain event when a vehicle is retired from service
 */
final class VehicleRetired
{
    public function __construct(
        public string $vehicleId,
        public string $tenantId,
        public \DateTime $retirementDate,
    ) {}
}
