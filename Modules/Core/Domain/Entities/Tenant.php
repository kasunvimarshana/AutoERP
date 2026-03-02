<?php

declare(strict_types=1);

namespace Modules\Core\Domain\Entities;

use Modules\Core\Domain\ValueObjects\TenantId;
use DateTimeImmutable;

class Tenant
{
    public function __construct(
        private readonly TenantId $id,
        private string $name,
        private string $slug,
        private ?string $domain,
        private string $plan,
        private bool $isActive,
        private readonly DateTimeImmutable $createdAt,
    ) {}

    public function getId(): TenantId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function getPlan(): string
    {
        return $this->plan;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Business rule: a tenant can only create organisations if the tenant is active.
     */
    public function canCreateOrganisation(): bool
    {
        return $this->isActive;
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function changePlan(string $newPlan): void
    {
        if (empty($newPlan)) {
            throw new \InvalidArgumentException('Plan cannot be empty.');
        }

        $this->plan = $newPlan;
    }
}
