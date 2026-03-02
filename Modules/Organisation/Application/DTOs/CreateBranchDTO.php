<?php

declare(strict_types=1);

namespace Modules\Organisation\Application\DTOs;

/**
 * Data Transfer Object for creating a Branch.
 */
final class CreateBranchDTO
{
    public function __construct(
        public readonly int|string $organisationId,
        public readonly string $name,
        public readonly string $code,
        public readonly ?string $address,
        public readonly bool $isActive,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            organisationId: $data['organisation_id'],
            name:           $data['name'],
            code:           $data['code'],
            address:        $data['address'] ?? null,
            isActive:       (bool) ($data['is_active'] ?? true),
        );
    }
}
