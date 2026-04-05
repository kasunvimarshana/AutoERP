<?php
declare(strict_types=1);
namespace Modules\HR\Domain\Entities;

class Position
{
    public function __construct(
        private ?int $id,
        private int $tenantId,
        private int $departmentId,
        private string $title,
        private string $code,
        private ?string $description,
        private ?string $employmentType,
        private ?float $minSalary,
        private ?float $maxSalary,
        private bool $isActive,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getDepartmentId(): int { return $this->departmentId; }
    public function getTitle(): string { return $this->title; }
    public function getCode(): string { return $this->code; }
    public function getDescription(): ?string { return $this->description; }
    public function getEmploymentType(): ?string { return $this->employmentType; }
    public function getMinSalary(): ?float { return $this->minSalary; }
    public function getMaxSalary(): ?float { return $this->maxSalary; }
    public function isActive(): bool { return $this->isActive; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    public function update(string $title, string $code, ?string $description, ?string $employmentType, ?float $minSalary, ?float $maxSalary): void
    {
        $this->title = $title;
        $this->code = $code;
        $this->description = $description;
        $this->employmentType = $employmentType;
        $this->minSalary = $minSalary;
        $this->maxSalary = $maxSalary;
    }

    public function activate(): void { $this->isActive = true; }
    public function deactivate(): void { $this->isActive = false; }
}
