<?php
declare(strict_types=1);
namespace Modules\User\Domain\Entities;

class User
{
    public function __construct(
        private ?int $id,
        private int $tenantId,
        private string $name,
        private string $email,
        private string $password,
        private string $status,
        private ?string $phone,
        private ?string $avatar,
        private ?array $preferences,
        private ?\DateTimeInterface $emailVerifiedAt,
        private ?\DateTimeInterface $createdAt,
        private ?\DateTimeInterface $updatedAt,
    ) {}

    public function getId(): ?int { return $this->id; }
    public function getTenantId(): int { return $this->tenantId; }
    public function getName(): string { return $this->name; }
    public function getEmail(): string { return $this->email; }
    public function getPassword(): string { return $this->password; }
    public function getStatus(): string { return $this->status; }
    public function getPhone(): ?string { return $this->phone; }
    public function getAvatar(): ?string { return $this->avatar; }
    public function getPreferences(): ?array { return $this->preferences; }
    public function getEmailVerifiedAt(): ?\DateTimeInterface { return $this->emailVerifiedAt; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function isActive(): bool { return $this->status === 'active'; }
    public function updateProfile(string $name, ?string $phone): void { $this->name = $name; $this->phone = $phone; }
    public function changePassword(string $hashedPassword): void { $this->password = $hashedPassword; }
    public function updateAvatar(?string $avatar): void { $this->avatar = $avatar; }
    public function updatePreferences(?array $preferences): void { $this->preferences = $preferences; }
    public function activate(): void { $this->status = 'active'; }
    public function deactivate(): void { $this->status = 'inactive'; }
}
