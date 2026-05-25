<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\DTOs;

final readonly class TenantQueryData
{
    public function __construct(
        public ?string $status,
        public ?bool $isActive,
        public ?string $search,
        public int $perPage,
        public int $page,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            isset($payload['status']) ? (string) $payload['status'] : null,
            array_key_exists('is_active', $payload) ? (bool) $payload['is_active'] : null,
            isset($payload['search']) ? (string) $payload['search'] : null,
            max(1, (int) ($payload['per_page'] ?? 20)),
            max(1, (int) ($payload['page'] ?? 1)),
        );
    }
}
