<?php

declare(strict_types=1);

namespace Modules\User\Domain\Entities;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\User\Domain\ValueObjects\Address;
use Modules\User\Domain\ValueObjects\Email;
use Modules\User\Domain\ValueObjects\PhoneNumber;
use Modules\User\Domain\ValueObjects\UserPreferences;

class User
{
    private ?int $id;

    private int $tenantId;

    private ?int $orgUnitId;

    private Email $email;

    private string $firstName;

    private string $lastName;

    private ?PhoneNumber $phone;

    private ?Address $address;

    private UserPreferences $preferences;

    private bool $active;

    private ?string $avatar;

    private Collection $roles; // Collection of Role entities

    private Collection $attachments; // Collection of UserAttachment entities

    private bool $attachmentsLoaded = false;

    private Collection $devices; // Collection of UserDevice entities

    private bool $devicesLoaded = false;

    private \DateTimeInterface $createdAt;

    private \DateTimeInterface $updatedAt;

    public function __construct(
        int $tenantId,
        ?int $orgUnitId,
        Email $email,
        string $firstName,
        string $lastName,
        ?PhoneNumber $phone = null,
        ?Address $address = null,
        ?UserPreferences $preferences = null,
        bool $active = true,
        ?int $id = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $updatedAt = null,
        ?string $avatar = null
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->orgUnitId = $orgUnitId;
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->phone = $phone;
        $this->address = $address;
        $this->preferences = $preferences ?? new UserPreferences;
        $this->active = $active;
        $this->avatar = $avatar;
        $this->roles = new Collection;
        $this->attachments = new Collection;
        $this->devices = new Collection;
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

    public function getOrgUnitId(): ?int
    {
        return $this->orgUnitId;
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

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function getRoles(): Collection
    {
        return $this->roles;
    }

    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    public function hasLoadedAttachments(): bool
    {
        return $this->attachmentsLoaded;
    }

    public function getDevices(): Collection
    {
        return $this->devices;
    }

    public function hasLoadedDevices(): bool
    {
        return $this->devicesLoaded;
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

    public function changeOrgUnit(?int $orgUnitId): void
    {
        $this->orgUnitId = $orgUnitId;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function changeEmail(Email $email): void
    {
        $this->email = $email;
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

    public function changeAvatar(?string $avatarPath): void
    {
        $this->avatar = $avatarPath;
        $this->updatedAt = new \DateTimeImmutable;
    }

    public function setAttachments(Collection $attachments): void
    {
        $this->attachments = $attachments;
        $this->attachmentsLoaded = true;
    }

    public function setDevices(Collection $devices): void
    {
        $this->devices = $devices;
        $this->devicesLoaded = true;
    }

    public function deactivate(): void
    {
        $this->active = false;
        $this->updatedAt = new \DateTimeImmutable;
    }
}
