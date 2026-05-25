<?php

declare(strict_types=1);

namespace Modules\Auth\Application\DTOs;

final readonly class RegistrationData
{
    public function __construct(
        public ?int $tenantId,
        public ?int $organizationUnitId,
        public string $providerKey,
        public string $firstName,
        public ?string $lastName,
        public string $email,
        public string $password,
        public ?array $metadata,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : null,
            isset($payload['organization_unit_id']) ? (int) $payload['organization_unit_id'] : null,
            (string) ($payload['provider_key'] ?? 'internal'),
            (string) ($payload['first_name'] ?? ''),
            isset($payload['last_name']) ? (string) $payload['last_name'] : null,
            (string) ($payload['email'] ?? ''),
            (string) ($payload['password'] ?? ''),
            isset($payload['metadata']) && is_array($payload['metadata']) ? $payload['metadata'] : null,
        );
    }
}
