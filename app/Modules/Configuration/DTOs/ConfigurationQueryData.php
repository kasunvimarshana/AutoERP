<?php

declare(strict_types=1);

namespace Modules\Configuration\DTOs;

final readonly class ConfigurationQueryData
{
    public function __construct(
        public ?string $prefix,
        public ?string $source,
        public int $page,
        public int $perPage,
        public ?string $scope = null,
        public ?int $tenantId = null,
        public ?int $organizationUnitId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            isset($payload['prefix']) ? (string) $payload['prefix'] : null,
            isset($payload['source']) ? (string) $payload['source'] : null,
            (int) ($payload['page'] ?? 0),
            (int) ($payload['per_page'] ?? $payload['perPage'] ?? 0),
            isset($payload['scope']) ? (string) $payload['scope'] : null,
            isset($payload['tenant_id']) && is_numeric($payload['tenant_id']) ? (int) $payload['tenant_id'] : null,
            isset($payload['organization_unit_id']) && is_numeric($payload['organization_unit_id'])
                ? (int) $payload['organization_unit_id']
                : null,
        );
    }
}
