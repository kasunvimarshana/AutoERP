<?php

namespace Modules\OrganizationUnit\Domain\Entities;

class OrganizationUnitAttachment
{
    private ?int $id;
    private int $tenantId;
    private int $organizationUnitId;
    private string $uuid;
    private string $name;
    private string $filePath;
    private string $mimeType;
    private int $size;
    private ?string $type;
    private ?array $metadata;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        int $organizationUnitId,
        string $uuid,
        string $name,
        string $filePath,
        string $mimeType,
        int $size,
        ?string $type = null,
        ?array $metadata = null,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->organizationUnitId = $organizationUnitId;
        $this->uuid = $uuid;
        $this->name = $name;
        $this->filePath = $filePath;
        $this->mimeType = $mimeType;
        $this->size = $size;
        $this->type = $type;
        $this->metadata = $metadata;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable();
    }

    // Getters...
    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getOrganizationUnitId(): int { return $this->organizationUnitId; }
    public function getUuid(): string { return $this->uuid; }
    public function getName(): string { return $this->name; }
    public function getFilePath(): string { return $this->filePath; }
    public function getMimeType(): string { return $this->mimeType; }
    public function getSize(): int { return $this->size; }
    public function getType(): ?string { return $this->type; }
    public function getMetadata(): ?array { return $this->metadata; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeInterface { return $this->updatedAt; }
}
