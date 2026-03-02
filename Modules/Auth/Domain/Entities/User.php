<?php

declare(strict_types=1);

namespace Modules\Auth\Domain\Entities;

use Modules\Auth\Domain\ValueObjects\Email;

class User
{
    public function __construct(
        private readonly int $id,
        private readonly int $tenantId,
        private readonly int $organisationId,
        private string $name,
        private readonly Email $email,
        private string $role,
        private bool $isActive,
        private ?string $passwordHash = null,
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getOrganisationId(): int
    {
        return $this->organisationId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getPasswordHash(): ?string
    {
        return $this->passwordHash;
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function changeName(string $name): void
    {
        if (empty(trim($name))) {
            throw new \InvalidArgumentException('User name cannot be empty.');
        }

        $this->name = $name;
    }

    public function changeRole(string $role): void
    {
        if (empty(trim($role))) {
            throw new \InvalidArgumentException('Role cannot be empty.');
        }

        $this->role = $role;
    }
}
