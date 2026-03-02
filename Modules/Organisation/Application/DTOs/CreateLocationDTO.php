<?php

declare(strict_types=1);

namespace Modules\Organisation\Application\DTOs;

/**
 * Data Transfer Object for creating a Location.
 */
final class CreateLocationDTO
{
    public function __construct(
        public readonly int|string $branchId,
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
            branchId:    $data['branch_id'],
            name:        $data['name'],
            code:        $data['code'],
            description: $data['description'] ?? null,
            isActive:    (bool) ($data['is_active'] ?? true),
        );
    }
}
