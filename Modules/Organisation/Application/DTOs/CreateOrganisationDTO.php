<?php

declare(strict_types=1);

namespace Modules\Organisation\Application\DTOs;

/**
 * Data Transfer Object for creating an Organisation.
 */
final class CreateOrganisationDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $code,
        public readonly ?string $description,
        public readonly bool $isActive,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            code: $data['code'],
            description: $data['description'] ?? null,
            isActive: (bool) ($data['is_active'] ?? true),
        );
    }
}
