<?php

declare(strict_types=1);

namespace Modules\Configuration\DTOs;

final readonly class ConfigurationMutationData
{
    public function __construct(
        public string $key,
        public mixed $value,
        public ?string $source = null,
        public ?string $description = null,
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
            (string) ($payload['key'] ?? ''),
            $payload['value'] ?? null,
            isset($payload['source']) ? (string) $payload['source'] : null,
            isset($payload['description']) ? (string) $payload['description'] : null,
            isset($payload['scope']) ? (string) $payload['scope'] : null,
            isset($payload['tenant_id']) && is_numeric($payload['tenant_id']) ? (int) $payload['tenant_id'] : null,
            isset($payload['organization_unit_id']) && is_numeric($payload['organization_unit_id'])
                ? (int) $payload['organization_unit_id']
                : null,
        );
    }
}
