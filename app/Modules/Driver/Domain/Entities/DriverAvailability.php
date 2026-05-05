<?php declare(strict_types=1);

namespace Modules\Driver\Domain\Entities;

/**
 * DriverAvailability Entity - Tracks driver availability for rental assignments
 *
 * Manages which days/times a driver is available for work.
 */
final class DriverAvailability
{
    private string $id;
    private string $tenantId;
    private string $driverId;
    private \DateTime $availableDate;
    private \DateTime $availableFrom;
    private \DateTime $availableUntil;
    private bool $isAvailable;
    private ?string $notes;
    private \DateTime $createdAt;
    private ?\DateTime $updatedAt;

    public function __construct(
        string $id,
        string $tenantId,
        string $driverId,
        \DateTime $availableDate,
        \DateTime $availableFrom,
        \DateTime $availableUntil,
        bool $isAvailable = true,
        ?string $notes = null,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->driverId = $driverId;
        $this->availableDate = $availableDate;
        $this->availableFrom = $availableFrom;
        $this->availableUntil = $availableUntil;
        $this->isAvailable = $isAvailable;
        $this->notes = $notes;
        $this->createdAt = new \DateTime();
        $this->updatedAt = null;
    }

    public function getId(): string { return $this->id; }
    public function getTenantId(): string { return $this->tenantId; }
    public function getDriverId(): string { return $this->driverId; }
    public function getAvailableDate(): \DateTime { return $this->availableDate; }
    public function getAvailableFrom(): \DateTime { return $this->availableFrom; }
    public function getAvailableUntil(): \DateTime { return $this->availableUntil; }
    public function isAvailable(): bool { return $this->isAvailable; }

    public function markAvailable(): void
    {
        $this->isAvailable = true;
        $this->updatedAt = new \DateTime();
    }

    public function markUnavailable(): void
    {
        $this->isAvailable = false;
        $this->updatedAt = new \DateTime();
    }
}
