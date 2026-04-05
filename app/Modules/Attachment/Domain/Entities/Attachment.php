<?php
declare(strict_types=1);
namespace Modules\Attachment\Domain\Entities;
class Attachment {
    public function __construct(
        private ?int $id, private int $tenantId, private string $attachableType, private int $attachableId,
        private string $filename, private string $originalName, private string $mimeType,
        private int $size, private string $path, private ?string $disk,
        private ?string $category, private ?int $uploadedBy,
        private ?\DateTimeInterface $createdAt, private ?\DateTimeInterface $updatedAt,
    ) {}
    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getAttachableType(): string { return $this->attachableType; }
    public function getAttachableId(): int { return $this->attachableId; }
    public function getFilename(): string { return $this->filename; }
    public function getOriginalName(): string { return $this->originalName; }
    public function getMimeType(): string { return $this->mimeType; }
    public function getSize(): int { return $this->size; }
    public function getPath(): string { return $this->path; }
    public function getDisk(): ?string { return $this->disk; }
    public function getCategory(): ?string { return $this->category; }
    public function getUploadedBy(): ?int { return $this->uploadedBy; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function isImage(): bool { return str_starts_with($this->mimeType,'image/'); }
    public function isPdf(): bool { return $this->mimeType === 'application/pdf'; }
}
