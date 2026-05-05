<?php declare(strict_types=1);

namespace Modules\Asset\Domain\Entities;

/**
 * Vehicle Entity - Specialized asset for vehicles (cars, trucks, motorcycles)
 *
 * Represents a vehicle asset with specific attributes like VIN, registration plate,
 * vehicle category, rental rates, mileage tracking, and location.
 *
 * @package Modules\Asset\Domain\Entities
 */
final class Vehicle
{
    /**
     * Unique identifier (UUID)
     *
     * @var string
     */
    private string $id;

    /**
     * Tenant ID for multi-tenancy
     *
     * @var string
     */
    private string $tenantId;

    /**
     * Reference to the Asset entity (one-to-one relationship)
     *
     * @var string (UUID)
     */
    private string $assetId;

    /**
     * Vehicle Identification Number (unique, immutable)
     *
     * @var string
     */
    private string $vin;

    /**
     * Registration/License plate number
     *
     * @var string
     */
    private string $registrationPlate;

    /**
     * Vehicle type/category
     *
     * Values: sedan, suv, truck, van, motorcycle, pickup, minibus
     *
     * @var string
     */
    private string $vehicleType;

    /**
     * Make/Manufacturer (e.g., Toyota, Honda, BMW)
     *
     * @var string
     */
    private string $make;

    /**
     * Model name
     *
     * @var string
     */
    private string $model;

    /**
     * Year of manufacture
     *
     * @var int
     */
    private int $year;

    /**
     * Color of the vehicle
     *
     * @var string
     */
    private string $color;

    /**
     * Fuel type
     *
     * Values: petrol, diesel, hybrid, electric, lpg
     *
     * @var string
     */
    private string $fuelType;

    /**
     * Transmission type
     *
     * Values: manual, automatic, cvt
     *
     * @var string
     */
    private string $transmission;

    /**
     * Number of seats
     *
     * @var int
     */
    private int $seatingCapacity;

    /**
     * Fuel tank capacity in liters
     *
     * @var string (DECIMAL for precision)
     */
    private string $fuelTankCapacity;

    /**
     * Engine displacement in cc
     *
     * @var int
     */
    private int $engineDisplacement;

    /**
     * Current mileage/odometer reading
     *
     * @var int (in km)
     */
    private int $currentMileage;

    /**
     * Current location (warehouse/bin)
     *
     * @var string|null (UUID reference to warehouse location)
     */
    private ?string $currentLocationId;

    /**
     * Is this vehicle available for rental?
     *
     * @var bool
     */
    private bool $isRentable;

    /**
     * Is this vehicle available for service/maintenance?
     *
     * @var bool
     */
    private bool $isServiceable;

    /**
     * Current vehicle status
     *
     * Values: available, rented, in_maintenance, damaged, retired
     *
     * @var string
     */
    private string $status;

    /**
     * Insurance policy number
     *
     * @var string|null
     */
    private ?string $insurancePolicyNumber;

    /**
     * Insurance expiry date
     *
     * @var \DateTime|null
     */
    private ?\DateTime $insuranceExpiryDate;

    /**
     * Last service date
     *
     * @var \DateTime|null
     */
    private ?\DateTime $lastServiceDate;

    /**
     * Next scheduled service date
     *
     * @var \DateTime|null
     */
    private ?\DateTime $nextServiceDate;

    /**
     * Mileage at which next service is due
     *
     * @var int|null
     */
    private ?int $nextServiceMileage;

    /**
     * Creation timestamp
     *
     * @var \DateTime
     */
    private \DateTime $createdAt;

    /**
     * Last update timestamp
     *
     * @var \DateTime|null
     */
    private ?\DateTime $updatedAt;

    /**
     * Soft delete timestamp
     *
     * @var \DateTime|null
     */
    private ?\DateTime $deletedAt;

    /**
     * Constructor
     *
     * @param string $id
     * @param string $tenantId
     * @param string $assetId
     * @param string $vin
     * @param string $registrationPlate
     * @param string $vehicleType
     * @param string $make
     * @param string $model
     * @param int $year
     * @param string $color
     * @param string $fuelType
     * @param string $transmission
     * @param int $seatingCapacity
     * @param string $fuelTankCapacity
     * @param int $engineDisplacement
     * @param int $currentMileage
     * @param string|null $currentLocationId
     * @param bool $isRentable
     * @param bool $isServiceable
     * @param string $status
     * @param string|null $insurancePolicyNumber
     * @param \DateTime|null $insuranceExpiryDate
     * @param \DateTime|null $lastServiceDate
     * @param \DateTime|null $nextServiceDate
     * @param int|null $nextServiceMileage
     */
    public function __construct(
        string $id,
        string $tenantId,
        string $assetId,
        string $vin,
        string $registrationPlate,
        string $vehicleType,
        string $make,
        string $model,
        int $year,
        string $color,
        string $fuelType,
        string $transmission,
        int $seatingCapacity,
        string $fuelTankCapacity,
        int $engineDisplacement,
        int $currentMileage,
        ?string $currentLocationId,
        bool $isRentable,
        bool $isServiceable,
        string $status,
        ?string $insurancePolicyNumber = null,
        ?\DateTime $insuranceExpiryDate = null,
        ?\DateTime $lastServiceDate = null,
        ?\DateTime $nextServiceDate = null,
        ?int $nextServiceMileage = null,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->assetId = $assetId;
        $this->vin = $vin;
        $this->registrationPlate = $registrationPlate;
        $this->vehicleType = $vehicleType;
        $this->make = $make;
        $this->model = $model;
        $this->year = $year;
        $this->color = $color;
        $this->fuelType = $fuelType;
        $this->transmission = $transmission;
        $this->seatingCapacity = $seatingCapacity;
        $this->fuelTankCapacity = $fuelTankCapacity;
        $this->engineDisplacement = $engineDisplacement;
        $this->currentMileage = $currentMileage;
        $this->currentLocationId = $currentLocationId;
        $this->isRentable = $isRentable;
        $this->isServiceable = $isServiceable;
        $this->status = $status;
        $this->insurancePolicyNumber = $insurancePolicyNumber;
        $this->insuranceExpiryDate = $insuranceExpiryDate;
        $this->lastServiceDate = $lastServiceDate;
        $this->nextServiceDate = $nextServiceDate;
        $this->nextServiceMileage = $nextServiceMileage;
        $this->createdAt = new \DateTime();
        $this->updatedAt = null;
        $this->deletedAt = null;
    }

    /**
     * Get vehicle ID
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get tenant ID
     *
     * @return string
     */
    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    /**
     * Get asset ID
     *
     * @return string
     */
    public function getAssetId(): string
    {
        return $this->assetId;
    }

    /**
     * Get VIN
     *
     * @return string
     */
    public function getVin(): string
    {
        return $this->vin;
    }

    /**
     * Get registration plate
     *
     * @return string
     */
    public function getRegistrationPlate(): string
    {
        return $this->registrationPlate;
    }

    /**
     * Get vehicle type
     *
     * @return string
     */
    public function getVehicleType(): string
    {
        return $this->vehicleType;
    }

    /**
     * Get make
     *
     * @return string
     */
    public function getMake(): string
    {
        return $this->make;
    }

    /**
     * Get model
     *
     * @return string
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Get year
     *
     * @return int
     */
    public function getYear(): int
    {
        return $this->year;
    }

    /**
     * Get current mileage
     *
     * @return int
     */
    public function getCurrentMileage(): int
    {
        return $this->currentMileage;
    }

    /**
     * Update mileage (only increases, never decreases)
     *
     * @param int $newMileage
     * @return void
     * @throws \DomainException if new mileage is less than current
     */
    public function updateMileage(int $newMileage): void
    {
        if ($newMileage < $this->currentMileage) {
            throw new \DomainException('Mileage cannot decrease');
        }
        $this->currentMileage = $newMileage;
        $this->updatedAt = new \DateTime();
    }

    /**
     * Get current location
     *
     * @return string|null
     */
    public function getCurrentLocationId(): ?string
    {
        return $this->currentLocationId;
    }

    /**
     * Update current location
     *
     * @param string|null $locationId
     * @return void
     */
    public function updateLocation(?string $locationId): void
    {
        $this->currentLocationId = $locationId;
        $this->updatedAt = new \DateTime();
    }

    /**
     * Is vehicle rentable?
     *
     * @return bool
     */
    public function isRentable(): bool
    {
        return $this->isRentable && $this->status === 'available';
    }

    /**
     * Is vehicle serviceable?
     *
     * @return bool
     */
    public function isServiceable(): bool
    {
        return $this->isServiceable && $this->status === 'available';
    }

    /**
     * Get current status
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Update vehicle status
     *
     * Valid transitions:
     * - available ↔ rented
     * - available ↔ in_maintenance
     * - any → damaged
     * - any → retired
     *
     * @param string $newStatus
     * @return void
     * @throws \DomainException if invalid state transition
     */
    public function updateStatus(string $newStatus): void
    {
        // Allow transitions to damaged or retired from any state
        if (in_array($newStatus, ['damaged', 'retired'], true)) {
            $this->status = $newStatus;
            $this->updatedAt = new \DateTime();
            return;
        }

        // Enforce valid state machine transitions
        $validTransitions = [
            'available' => ['rented', 'in_maintenance'],
            'rented' => ['available'],
            'in_maintenance' => ['available'],
        ];

        if (!isset($validTransitions[$this->status]) || !in_array($newStatus, $validTransitions[$this->status], true)) {
            throw new \DomainException(
                "Cannot transition from '{$this->status}' to '{$newStatus}'"
            );
        }

        $this->status = $newStatus;
        $this->updatedAt = new \DateTime();
    }

    /**
     * Check if insurance is expired
     *
     * @return bool
     */
    public function isInsuranceExpired(): bool
    {
        if ($this->insuranceExpiryDate === null) {
            return true;
        }
        return $this->insuranceExpiryDate < new \DateTime();
    }

    /**
     * Update insurance details
     *
     * @param string $policyNumber
     * @param \DateTime $expiryDate
     * @return void
     */
    public function updateInsurance(string $policyNumber, \DateTime $expiryDate): void
    {
        $this->insurancePolicyNumber = $policyNumber;
        $this->insuranceExpiryDate = $expiryDate;
        $this->updatedAt = new \DateTime();
    }

    /**
     * Update service dates
     *
     * @param \DateTime $lastServiceDate
     * @param \DateTime|null $nextServiceDate
     * @param int|null $nextServiceMileage
     * @return void
     */
    public function updateServiceDates(
        \DateTime $lastServiceDate,
        ?\DateTime $nextServiceDate = null,
        ?int $nextServiceMileage = null,
    ): void {
        $this->lastServiceDate = $lastServiceDate;
        $this->nextServiceDate = $nextServiceDate;
        $this->nextServiceMileage = $nextServiceMileage;
        $this->updatedAt = new \DateTime();
    }

    /**
     * Get creation timestamp
     *
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * Soft delete the vehicle
     *
     * @return void
     */
    public function delete(): void
    {
        $this->deletedAt = new \DateTime();
        $this->status = 'retired';
    }

    /**
     * Check if deleted
     *
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }
}
