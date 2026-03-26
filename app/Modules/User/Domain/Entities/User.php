<?php

declare(strict_types=1);

namespace Modules\User\Domain\Entities;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\Core\Domain\ValueObjects\Address;
use Modules\Core\Domain\ValueObjects\Email;
use Modules\Core\Domain\ValueObjects\PhoneNumber;
use Modules\Core\Domain\ValueObjects\UserPreferences;

class User
{
    private ?int $id;

    private int $tenantId;

    private Email $email;

    private string $firstName;

    private string $lastName;

    private ?PhoneNumber $phone;

    private ?Address $address;

    private UserPreferences $preferences;

    private bool $active;

    private Collection $roles; // Collection of Role entities

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        Email $email,
        string $firstName,
        string $lastName,
        ?PhoneNumber $phone = null,
        ?Address $address = null,
        ?UserPreferences $preferences = null,
        bool $active = true,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->phone = $phone;
        $this->address = $address;
        $this->preferences = $preferences ?? new UserPreferences;
        $this->active = $active;
        $this->roles = new Collection;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable;
        $this->updatedAt = $updatedAt ?? new \DateTimeImmutable;
    }

    // Getters...
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getFullName(): string
    {
        return $this->firstName.' '.$this->lastName;
    }

    public function getPhone(): ?PhoneNumber
    {
        return $this->phone;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function getPreferences(): UserPreferences
    {
        return $this->preferences;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getRoles(): Collection
    {
        return $this->roles;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    // Domain behaviour
    public function assignRole(Role $role): void
    {
        if ($role->getTenantId() !== $this->tenantId) {
            throw new DomainException('Role does not belong to the same tenant');
        }
        if (! $this->roles->contains('id', $role->getId())) {
            $this->roles->add($role);
        }
    }

    public function removeRole(Role $role): void
    {
        $this->roles = $this->roles->reject(fn ($r) => $r->getId() === $role->getId());
    }

    public function hasPermission(string $permissionName): bool
    {
        foreach ($this->roles as $role) {
            if ($role->hasPermission($permissionName)) {
                return true;
            }
        }

        return false;
    }

    public function updateProfile(string $firstName, string $lastName, ?PhoneNumber $phone, ?Address $address): void
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->phone = $phone;
        $this->address = $address;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function updatePreferences(UserPreferences $preferences): void
    {
        $this->preferences = $preferences;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function activate(): void
    {
        $this->active = true;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function deactivate(): void
    {
        $this->active = false;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
