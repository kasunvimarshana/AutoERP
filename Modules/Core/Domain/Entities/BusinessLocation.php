<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Entities;

/**
 * BusinessLocation: a physical or logical location (outlet, warehouse, branch) belonging to an organisation.
 * Derived from the BusinessLocation model in the PHP_POS reference repository.
 */
class BusinessLocation
{
    public function __construct(
        private readonly int     $id,
        private readonly int     $tenantId,
        private string           $name,
        private ?string          $address,
        private ?string          $city,
        private ?string          $state,
        private ?string          $country,
        private ?string          $phone,
        private ?string          $email,
        private bool             $isActive,
    ) {}

    public function getId(): int         { return $this->id; }
    public function getTenantId(): int   { return $this->tenantId; }
    public function getName(): string    { return $this->name; }
    public function getAddress(): ?string { return $this->address; }
    public function getCity(): ?string   { return $this->city; }
    public function getState(): ?string  { return $this->state; }
    public function getCountry(): ?string { return $this->country; }
    public function getPhone(): ?string  { return $this->phone; }
    public function getEmail(): ?string  { return $this->email; }
    public function isActive(): bool     { return $this->isActive; }

    public function rename(string $name): void
    {
        if (empty(trim($name))) {
            throw new \InvalidArgumentException('Business location name cannot be empty.');
        }
        $this->name = $name;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }
}
