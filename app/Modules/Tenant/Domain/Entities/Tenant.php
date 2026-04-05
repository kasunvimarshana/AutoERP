<?php
declare(strict_types=1);
namespace Modules\Tenant\Domain\Entities;

class Tenant
{
    public function __construct(
        private ?int $id,
        private string $name,
        private string $slug,
        private string $status,
        private ?string $planType,
        private ?array $settings,
        private ?\DateTimeInterface $trialEndsAt,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getSlug(): string { return $this->slug; }
    public function getStatus(): string { return $this->status; }
    public function getPlanType(): ?string { return $this->planType; }
    public function getSettings(): ?array { return $this->settings; }
    public function getTrialEndsAt(): ?\DateTimeInterface { return $this->trialEndsAt; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function isActive(): bool { return $this->status === 'active'; }
    public function activate(): void { $this->status = 'active'; }
    public function suspend(): void { $this->status = 'suspended'; }
    public function cancel(): void { $this->status = 'cancelled'; }
    public function updateName(string $name): void { $this->name = $name; }
    public function updateSettings(?array $settings): void { $this->settings = $settings; }
}
