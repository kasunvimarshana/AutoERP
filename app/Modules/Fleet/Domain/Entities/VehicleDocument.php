<?php

declare(strict_types=1);

namespace Modules\Fleet\Domain\Entities;

class VehicleDocument
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $vehicleId,
        public readonly string $documentType,
        public readonly ?string $documentNumber,
        public readonly ?string $issuingAuthority,
        public readonly ?string $issueDate,
        public readonly ?string $expiryDate,
        public readonly ?string $filePath,
        public readonly ?string $notes,
        public readonly bool $isActive = true,
        public readonly ?int $id = null,
    ) {}

    public function isExpired(): bool
    {
        if ($this->expiryDate === null) {
            return false;
        }

        return new \DateTimeImmutable($this->expiryDate) < new \DateTimeImmutable('today');
    }

    public function daysUntilExpiry(): ?int
    {
        if ($this->expiryDate === null) {
            return null;
        }

        $diff = (new \DateTimeImmutable('today'))->diff(new \DateTimeImmutable($this->expiryDate));

        return $diff->invert === 1 ? -$diff->days : $diff->days;
    }
}
