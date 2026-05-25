<?php

declare(strict_types=1);

namespace Modules\Auth\Application\DTOs;

final readonly class LogoutData
{
    public function __construct(
        public ?int $tenantId,
        public ?int $sessionId,
        public ?string $accessToken,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : null,
            isset($payload['session_id']) ? (int) $payload['session_id'] : null,
            isset($payload['access_token']) ? (string) $payload['access_token'] : null,
        );
    }
}
