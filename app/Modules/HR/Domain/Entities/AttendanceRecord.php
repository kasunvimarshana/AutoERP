<?php
declare(strict_types=1);
namespace Modules\HR\Domain\Entities;

class AttendanceRecord
{
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_BIOMETRIC = 'biometric';
    public const SOURCE_SYSTEM = 'system';

    public const TYPE_CHECK_IN = 'check_in';
    public const TYPE_CHECK_OUT = 'check_out';

    public function __construct(
        private ?int $id,
        private int $tenantId,
        private int $employeeId,
        private \DateTimeInterface $attendanceDate,
        private ?\DateTimeInterface $checkIn,
        private ?\DateTimeInterface $checkOut,
        private ?float $workedHours,
        private string $source,
        private ?string $deviceId,
        private ?string $biometricData,
        private ?string $notes,
        private bool $isApproved,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getEmployeeId(): int { return $this->employeeId; }
    public function getAttendanceDate(): \DateTimeInterface { return $this->attendanceDate; }
    public function getCheckIn(): ?\DateTimeInterface { return $this->checkIn; }
    public function getCheckOut(): ?\DateTimeInterface { return $this->checkOut; }
    public function getWorkedHours(): ?float { return $this->workedHours; }
    public function getSource(): string { return $this->source; }
    public function getDeviceId(): ?string { return $this->deviceId; }
    public function getBiometricData(): ?string { return $this->biometricData; }
    public function getNotes(): ?string { return $this->notes; }
    public function isApproved(): bool { return $this->isApproved; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    public function checkOut(\DateTimeInterface $checkOutTime): void
    {
        $this->checkOut = $checkOutTime;
        if ($this->checkIn !== null) {
            $diff = $checkOutTime->getTimestamp() - $this->checkIn->getTimestamp();
            $this->workedHours = round($diff / 3600, 2);
        }
    }

    public function approve(): void { $this->isApproved = true; }
    public function unapprove(): void { $this->isApproved = false; }

    public function updateNotes(?string $notes): void { $this->notes = $notes; }
}
