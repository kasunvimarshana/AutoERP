<?php

declare(strict_types=1);

namespace Modules\Auth\DTOs;

final readonly class LoginData
{
    public function __construct(
        public ?int $tenantId,
        public ?int $organizationUnitId,
        public string $providerKey,
        public string $loginIdentifier,
        public string $password,
        public ?string $ipAddress,
        public ?string $userAgent,
        public ?string $deviceName,
        public ?array $metadata,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : null,
            isset($payload['organization_unit_id']) ? (int) $payload['organization_unit_id'] : null,
            (string) ($payload['provider_key'] ?? 'internal'),
            (string) ($payload['login_identifier'] ?? ''),
            (string) ($payload['password'] ?? ''),
            isset($payload['ip_address']) ? (string) $payload['ip_address'] : null,
            isset($payload['user_agent']) ? (string) $payload['user_agent'] : null,
            isset($payload['device_name']) ? (string) $payload['device_name'] : null,
            isset($payload['metadata']) && is_array($payload['metadata']) ? $payload['metadata'] : null,
        );
    }
}
