<?php

declare(strict_types=1);

namespace Modules\Auth\Application\DTOs;

final readonly class VerificationChallengeVerifyData
{
    public function __construct(
        public ?int $tenantId,
        public string $challengeKey,
        public string $challengeSecret,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : null,
            (string) ($payload['challenge_key'] ?? ''),
            (string) ($payload['challenge_secret'] ?? ''),
        );
    }
}
