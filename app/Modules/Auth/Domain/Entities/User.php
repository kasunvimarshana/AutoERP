<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Entities;

/**
 * Domain entity representing a User.
 * This is a plain PHP object used within the domain layer.
 */
final class User
{
    public function __construct(
        public readonly int $id,
        public readonly string $uuid,
        public readonly int $tenantId,
        public readonly string $name,
        public readonly string $email,
        public readonly string $status,
        public readonly ?string $phone = null,
        public readonly ?string $locale = null,
        public readonly ?string $timezone = null,
        public readonly ?\DateTimeInterface $emailVerifiedAt = null,
        public readonly ?\DateTimeInterface $lastLoginAt = null,
        public readonly ?string $lastLoginIp = null,
        public readonly bool $twoFactorEnabled = false,
        public readonly ?array $preferences = null,
        public readonly ?array $metadata = null,
        public readonly ?\DateTimeInterface $createdAt = null,
        public readonly ?\DateTimeInterface $updatedAt = null,
    ) {}

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerifiedAt !== null;
    }
}
