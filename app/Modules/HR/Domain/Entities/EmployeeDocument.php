<?php

declare(strict_types=1);

namespace Modules\HR\Domain\Entities;

class EmployeeDocument
{
    public function __construct(
        private readonly int $tenantId,
        private readonly int $employeeId,
        private string $documentType,
        private string $title,
        private string $description,
        private string $filePath,
        private string $mimeType,
        private int $fileSize,
        private ?\DateTimeInterface $issuedDate,
        private ?\DateTimeInterface $expiryDate,
        private array $metadata,
        private readonly \DateTimeInterface $createdAt,
        private \DateTimeInterface $updatedAt,
        private ?int $id = null,
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getEmployeeId(): int
    {
        return $this->employeeId;
    }

    public function getDocumentType(): string
    {
        return $this->documentType;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function getIssuedDate(): ?\DateTimeInterface
    {
        return $this->issuedDate;
    }

    public function getExpiryDate(): ?\DateTimeInterface
    {
        return $this->expiryDate;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function isExpired(): bool
    {
        if ($this->expiryDate === null) {
            return false;
        }

        return $this->expiryDate < new \DateTimeImmutable;
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        if ($this->expiryDate === null) {
            return false;
        }

        $threshold = (new \DateTimeImmutable)->modify("+{$days} days");

        return $this->expiryDate <= $threshold && ! $this->isExpired();
    }
}
