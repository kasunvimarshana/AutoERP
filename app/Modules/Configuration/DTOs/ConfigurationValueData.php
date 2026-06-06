<?php

declare(strict_types=1);

namespace Modules\Configuration\DTOs;

final readonly class ConfigurationValueData
{
    public function __construct(
        public string $key,
        public mixed $value,
        public string $source,
        public ?string $description = null,
        public ?string $updatedAt = null,
        public ?string $scope = null,
        public ?int $tenantId = null,
        public ?int $organizationUnitId = null,
        public ?string $resolvedFrom = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'value' => $this->value,
            'source' => $this->source,
            'description' => $this->description,
            'updated_at' => $this->updatedAt,
            'scope' => $this->scope,
            'tenant_id' => $this->tenantId,
            'organization_unit_id' => $this->organizationUnitId,
            'resolved_from' => $this->resolvedFrom,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            (string) ($payload['key'] ?? ''),
            $payload['value'] ?? null,
            (string) ($payload['source'] ?? ''),
            isset($payload['description']) ? (string) $payload['description'] : null,
            isset($payload['updated_at']) ? (string) $payload['updated_at'] : null,
            isset($payload['scope']) ? (string) $payload['scope'] : null,
            isset($payload['tenant_id']) && is_numeric($payload['tenant_id']) ? (int) $payload['tenant_id'] : null,
            isset($payload['organization_unit_id']) && is_numeric($payload['organization_unit_id'])
                ? (int) $payload['organization_unit_id']
                : null,
            isset($payload['resolved_from']) ? (string) $payload['resolved_from'] : null,
        );
    }
}
