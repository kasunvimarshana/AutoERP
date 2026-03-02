<?php

declare(strict_types=1);

namespace Modules\Integration\Application\DTOs;

/**
 * Data Transfer Object for registering a webhook endpoint.
 */
final class RegisterWebhookDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $url,
        public readonly array $events,
        public readonly ?string $secret,
        public readonly array $headers,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name:    (string) $data['name'],
            url:     (string) $data['url'],
            events:  (array) $data['events'],
            secret:  isset($data['secret']) ? (string) $data['secret'] : null,
            headers: isset($data['headers']) ? (array) $data['headers'] : [],
        );
    }
}
