<?php

namespace App\DTOs;

/**
 * Data Transfer Object representing a tenant's runtime configuration.
 *
 * Sections:
 *  - mail:          SMTP/mailgun settings
 *  - payment:       Payment gateway credentials and settings
 *  - notifications: Push, Slack, webhook notification settings
 *  - features:      Feature flags (key => bool)
 *  - limits:        Resource limits (e.g. max_users, max_storage_gb)
 */
final class TenantConfigDTO
{
    public function __construct(
        public readonly array $mail          = [],
        public readonly array $payment       = [],
        public readonly array $notifications = [],
        public readonly array $features      = [],
        public readonly array $limits        = [],
    ) {}

    // -------------------------------------------------------------------------
    // Factory
    // -------------------------------------------------------------------------

    public static function fromArray(array $config): self
    {
        return new self(
            mail:          (array) ($config['mail']          ?? []),
            payment:       (array) ($config['payment']       ?? []),
            notifications: (array) ($config['notifications'] ?? []),
            features:      (array) ($config['features']      ?? []),
            limits:        (array) ($config['limits']        ?? []),
        );
    }

    // -------------------------------------------------------------------------
    // Serialization
    // -------------------------------------------------------------------------

    public function toArray(): array
    {
        return [
            'mail'          => $this->mail,
            'payment'       => $this->payment,
            'notifications' => $this->notifications,
            'features'      => $this->features,
            'limits'        => $this->limits,
        ];
    }

    // -------------------------------------------------------------------------
    // Convenience
    // -------------------------------------------------------------------------

    public function isFeatureEnabled(string $key): bool
    {
        return (bool) ($this->features[$key] ?? false);
    }

    public function getLimit(string $key, mixed $default = null): mixed
    {
        return $this->limits[$key] ?? $default;
    }

    public function getPaymentGateway(): string
    {
        return (string) ($this->payment['gateway'] ?? 'stripe');
    }

    public function getMailDriver(): string
    {
        return (string) ($this->mail['driver'] ?? config('mail.default', 'smtp'));
    }
}
