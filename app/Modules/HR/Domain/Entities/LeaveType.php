<?php
declare(strict_types=1);
namespace Modules\HR\Domain\Entities;

class LeaveType
{
    public function __construct(
        private ?int $id,
        private int $tenantId,
        private string $name,
        private string $code,
        private ?string $description,
        private int $defaultDays,
        private bool $isPaid,
        private bool $requiresApproval,
        private bool $isActive,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getName(): string { return $this->name; }
    public function getCode(): string { return $this->code; }
    public function getDescription(): ?string { return $this->description; }
    public function getDefaultDays(): int { return $this->defaultDays; }
    public function isPaid(): bool { return $this->isPaid; }
    public function requiresApproval(): bool { return $this->requiresApproval; }
    public function isActive(): bool { return $this->isActive; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    public function update(string $name, string $code, ?string $description, int $defaultDays, bool $isPaid, bool $requiresApproval): void
    {
        $this->name = $name;
        $this->code = $code;
        $this->description = $description;
        $this->defaultDays = $defaultDays;
        $this->isPaid = $isPaid;
        $this->requiresApproval = $requiresApproval;
    }

    public function activate(): void { $this->isActive = true; }
    public function deactivate(): void { $this->isActive = false; }
}
