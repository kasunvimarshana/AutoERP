<?php

declare(strict_types=1);

namespace Modules\Organisation\Application\DTOs;

/**
 * Data Transfer Object for creating a Department.
 */
final class CreateDepartmentDTO
{
    public function __construct(
        public readonly int|string $locationId,
        public readonly string $name,
        public readonly string $code,
        public readonly bool $isActive,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            locationId: $data['location_id'],
            name:       $data['name'],
            code:       $data['code'],
            isActive:   (bool) ($data['is_active'] ?? true),
        );
    }
}
