<?php

declare(strict_types=1);

namespace Modules\Tenancy\Application\DTOs;

use Modules\Core\Application\DTOs\DataTransferObject;

/**
 * Data Transfer Object for creating a Tenant.
 *
 * Carries validated tenant creation data from the controller to the service.
 * All fields are immutable after construction.
 */
final class CreateTenantDTO extends DataTransferObject
{
    public function __construct(
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $domain,
        public readonly bool $isActive,
        public readonly bool $pharmaComplianceMode,
    ) {}

    /**
     * {@inheritdoc}
     */
    public static function fromArray(array $data): static
    {
        return new static(
            name: $data['name'],
            slug: $data['slug'],
            domain: $data['domain'] ?? null,
            isActive: (bool) ($data['is_active'] ?? true),
            pharmaComplianceMode: (bool) ($data['pharma_compliance_mode'] ?? false),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return [
            'name'                   => $this->name,
            'slug'                   => $this->slug,
            'domain'                 => $this->domain,
            'is_active'              => $this->isActive,
            'pharma_compliance_mode' => $this->pharmaComplianceMode,
        ];
    }
}
