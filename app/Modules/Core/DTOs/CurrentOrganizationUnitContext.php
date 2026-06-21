<?php

declare(strict_types=1);

namespace Modules\Core\DTOs;

use InvalidArgumentException;

final readonly class CurrentOrganizationUnitContext
{
    public function __construct(
        private DataRecord $organizationUnit,
        private int $organizationUnitId,
        private int $tenantId,
        private ?string $code,
        private ?string $path,
        private string $name,
        private bool $isActive,
        private ?string $applicationId,
        private string $source,
    ) {
        if ($this->organizationUnitId < 1 || $this->tenantId < 1) {
            throw new InvalidArgumentException(
                'Current organization unit and tenant identifiers must be positive.',
            );
        }

        if ((int) $this->organizationUnit->id() !== $this->organizationUnitId) {
            throw new InvalidArgumentException(
                'Current organization unit record does not match the organization unit identifier.',
            );
        }

        if ((int) $this->organizationUnit->require('tenant_id') !== $this->tenantId) {
            throw new InvalidArgumentException(
                'Current organization unit record does not belong to the tenant context.',
            );
        }

        if (trim($this->name) === '' || trim($this->source) === '') {
            throw new InvalidArgumentException('Current organization unit name and source are required.');
        }
    }

    public function organizationUnit(): DataRecord
    {
        return $this->organizationUnit;
    }

    public function organizationUnitId(): int
    {
        return $this->organizationUnitId;
    }

    public function tenantId(): int
    {
        return $this->tenantId;
    }

    public function code(): ?string
    {
        return $this->code;
    }

    public function path(): ?string
    {
        return $this->path;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function applicationId(): ?string
    {
        return $this->applicationId;
    }

    public function source(): string
    {
        return $this->source;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'organization_unit' => $this->organizationUnit->toArray(),
            'organization_unit_id' => $this->organizationUnitId,
            'tenant_id' => $this->tenantId,
            'organization_unit_code' => $this->code,
            'organization_unit_path' => $this->path,
            'organization_unit_name' => $this->name,
            'is_active' => $this->isActive,
            'application_id' => $this->applicationId,
            'source' => $this->source,
        ];
    }
}
